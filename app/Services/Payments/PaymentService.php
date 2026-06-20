<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\CollectorCommission;
use App\Models\CompanySetting;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Services\Audit\AuditService;
use App\Services\Cash\CashMovementService;
use App\Services\Collectors\CollectorCommissionService;
use App\Services\Loans\LateFeeService;
use App\Services\Loans\LoanCalculatorService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        private readonly LateFeeService $lateFeeService,
        private readonly CashMovementService $cashMovementService,
        private readonly CollectorCommissionService $commissionService,
        private readonly AuditService $auditService,
        private readonly LoanCalculatorService $calculator,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForCompany(int $companyId, array $filters = []): LengthAwarePaginator
    {
        return Payment::query()
            ->with(['loan:id,loan_number', 'client:id,full_name', 'collector:id,name'])
            ->forCompany($companyId)
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('receipt_number', 'like', "%{$search}%")
                        ->orWhereHas('client', fn (Builder $client) => $client->where('full_name', 'like', "%{$search}%"))
                        ->orWhereHas('loan', fn (Builder $loan) => $loan->where('loan_number', 'like', "%{$search}%"));
                });
            })
            ->when($filters['payment_method'] ?? null, fn (Builder $query, string $method) => $query->where('payment_method', $method))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('payment_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('payment_date', '<=', $date))
            ->latest('payment_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Cubetas que cada modo de reparto puede afectar, en orden de prioridad.
     */
    private const ALLOCATION_BUCKETS = [
        'auto' => ['late', 'interest', 'principal'],
        'principal_and_interest' => ['interest', 'principal'],
        'interest_only' => ['interest'],
        'principal_only' => ['principal'],
        'current_plus_capital' => ['late', 'interest', 'principal'],
    ];

    /**
     * @param  array{loan_id:int, amount?:float, payment_date:string, payment_method:string, allocation_mode?:string, target_installment_id?:int|null, excess_action?:string|null, capital_prepayment_amount?:float|null, allocations?:array<int,array{installment_id:int, amount:float}>, mobile_uuid?:string|null, collector_id?:int|null, created_by?:int|null}  $data
     */
    public function register(array $data): Payment
    {
        $mode = $data['allocation_mode'] ?? 'auto';

        if ($mode !== 'custom' && ! isset(self::ALLOCATION_BUCKETS[$mode])) {
            throw new InvalidArgumentException('Modo de reparto de pago inválido.');
        }

        return DB::transaction(function () use ($data, $mode): Payment {
            /** @var Loan $loan */
            $loan = Loan::query()
                ->whereKey($data['loan_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($loan->status, ['active', 'late'], true)) {
                throw new InvalidArgumentException('Solo se pueden registrar pagos a préstamos activos o atrasados.');
            }

            $mobileUuid = $data['mobile_uuid'] ?? null;
            if ($mobileUuid) {
                $existingPayment = Payment::query()
                    ->where('company_id', $loan->company_id)
                    ->where('mobile_uuid', $mobileUuid)
                    ->lockForUpdate()
                    ->first();

                if ($existingPayment) {
                    if (
                        (int) $existingPayment->loan_id !== (int) $loan->id
                        || (int) $existingPayment->collector_id !== (int) ($data['collector_id'] ?? $loan->collector_id)
                    ) {
                        throw new InvalidArgumentException('Este identificador móvil ya fue usado por otro cobro.');
                    }

                    return $existingPayment->fresh(['details']) ?? $existingPayment;
                }
            }

            $previousBalance = (float) $loan->remaining_balance;
            $paymentDate = CarbonImmutable::parse($data['payment_date']);

            /** @var Collection<int, LoanInstallment> $installments */
            $installments = $loan->installments()
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->orderBy('due_date')
                ->orderBy('installment_number')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Refresca la mora de cada cuota a la fecha del pago antes de repartir.
            foreach ($installments as $installment) {
                $this->lateFeeService->refreshInstallment($loan, $installment, $paymentDate);
            }

            $payment = Payment::query()->create([
                'company_id' => $loan->company_id,
                'loan_id' => $loan->id,
                'client_id' => $loan->client_id,
                'collector_id' => $data['collector_id'] ?? $loan->collector_id,
                'receipt_number' => $this->receiptNumber((int) $loan->company_id),
                'mobile_uuid' => $mobileUuid,
                'payment_date' => $data['payment_date'],
                'amount' => 0,
                'payment_method' => $data['payment_method'],
                'previous_balance' => $previousBalance,
                'new_balance' => $previousBalance,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $targetInstallmentId = $data['target_installment_id'] ?? null;
            if ($mode === 'current_plus_capital' && $targetInstallmentId === null) {
                $targetInstallmentId = $installments->keys()->first();
            }

            $totals = $mode === 'custom'
                ? $this->allocateCustom($payment, $installments, $data['allocations'] ?? [])
                : $this->allocatePooled($payment, $installments, self::ALLOCATION_BUCKETS[$mode], (float) ($data['amount'] ?? 0), $targetInstallmentId);

            $leftover = round($totals['leftover'] ?? 0, 2);
            $capitalPrepaid = 0.0;
            $changeGiven = 0.0;
            $hasExplicitCapitalPrepayment = array_key_exists('capital_prepayment_amount', $data);
            $explicitCapitalPrepayment = $hasExplicitCapitalPrepayment
                ? round((float) ($data['capital_prepayment_amount'] ?? 0), 2)
                : null;

            if ($mode === 'current_plus_capital' && $hasExplicitCapitalPrepayment && $explicitCapitalPrepayment <= 0) {
                throw new InvalidArgumentException('Indica cuanto se abonara al capital.');
            }

            // Manejo del excedente: abono a capital (recalcula cuotas) o vuelto al cliente.
            if ($leftover > 0) {
                $excessAction = $mode === 'current_plus_capital'
                    ? 'prepayment'
                    : ($data['excess_action'] ?? 'reject');

                if ($excessAction === 'prepayment') {
                    if (! $loan->allows_capital_prepayment) {
                        throw new InvalidArgumentException('Este préstamo no permite abono a capital.');
                    }
                    $requestedCapitalPrepayment = $explicitCapitalPrepayment ?? $leftover;

                    if ($requestedCapitalPrepayment < 0) {
                        throw new InvalidArgumentException('El abono a capital no puede ser negativo.');
                    }

                    if ($requestedCapitalPrepayment - $leftover > 0.01) {
                        throw new InvalidArgumentException('El abono a capital no puede exceder el sobrante del pago.');
                    }

                    if ($requestedCapitalPrepayment > 0) {
                        $capitalPrepaid = $this->applyCapitalPrepayment($loan, $requestedCapitalPrepayment, $paymentDate);
                    }

                    $remainingLeftover = round($leftover - $capitalPrepaid, 2);
                    if ($remainingLeftover > 0) {
                        if (($data['excess_action'] ?? 'reject') === 'change') {
                            $changeGiven = $remainingLeftover;
                        } else {
                            throw new InvalidArgumentException('El monto excede la cuota mas el abono a capital indicado. Ajusta el abono o marca vuelto al cliente.');
                        }
                    }
                } elseif ($excessAction === 'change') {
                    $changeGiven = $leftover;
                } else {
                    throw new InvalidArgumentException('El monto excede lo que se puede aplicar. Indica si el excedente es abono a capital o vuelto al cliente.');
                }
            } elseif ($mode === 'current_plus_capital' && $hasExplicitCapitalPrepayment) {
                throw new InvalidArgumentException('El monto a cobrar debe incluir una cuota mas el abono a capital indicado.');
            }

            $principalPaid = round($totals['principal'] + $capitalPrepaid, 2);
            $interestPaid = $totals['interest'];
            $lateFeePaid = $totals['late'];
            $chargedAmount = round($principalPaid + $interestPaid + $lateFeePaid, 2);

            if ($chargedAmount <= 0) {
                throw new InvalidArgumentException('El pago no aplicó a ninguna cuota. Revisa el monto o las cuotas seleccionadas.');
            }

            // Si el negocio no permite pagos parciales, ninguna cuota tocada puede quedar a medias.
            // Modos que cubren solo parte de la cuota intencionalmente se eximen de esta regla.
            $allowPartial = (bool) (CompanySetting::query()->where('company_id', $loan->company_id)->value('allow_partial_payments') ?? true);
            $explicitPartialMode = in_array($mode, ['interest_only', 'principal_only', 'principal_and_interest'], true);
            if (! $allowPartial && ! $explicitPartialMode) {
                $touchedIds = $payment->details()->pluck('installment_id');
                $hasPartial = LoanInstallment::query()->whereIn('id', $touchedIds)->where('status', 'partial')->exists();
                if ($hasPartial) {
                    throw new InvalidArgumentException('Este negocio no permite pagos parciales: cubre la cuota completa.');
                }
            }

            // El balance y los totales se derivan del calendario (consistente tras re-amortizar).
            $loan->refresh();
            $newBalance = max(0, round((float) $loan->principal_amount - ((float) $loan->paid_principal + $totals['principal'] + $capitalPrepaid), 2));
            $scheduleInterest = round((float) $loan->installments()->where('status', '!=', 'cancelled')->sum('interest_amount'), 2);

            $loan->forceFill([
                'paid_principal' => round((float) $loan->paid_principal + $totals['principal'] + $capitalPrepaid, 2),
                'paid_interest' => round((float) $loan->paid_interest + $interestPaid, 2),
                'paid_late_fee' => round((float) $loan->paid_late_fee + $lateFeePaid, 2),
                'remaining_balance' => $newBalance,
                'total_interest' => $scheduleInterest,
                'total_amount' => round((float) $loan->principal_amount + $scheduleInterest, 2),
                'status' => $newBalance <= 0 ? 'paid' : $loan->status,
                'end_date' => $newBalance <= 0 ? now()->toDateString() : $loan->end_date,
            ])->save();

            $payment->forceFill([
                'amount' => $chargedAmount,
                'principal_paid' => $principalPaid,
                'interest_paid' => $interestPaid,
                'late_fee_paid' => $lateFeePaid,
                'capital_prepaid' => $capitalPrepaid,
                'change_given' => $changeGiven,
                'new_balance' => $newBalance,
            ])->save();

            $this->cashMovementService->create(
                companyId: (int) $loan->company_id,
                type: 'payment_received',
                amount: (float) $payment->amount,
                direction: 'in',
                reference: $payment,
                description: "Pago recibido {$payment->receipt_number}",
                createdBy: $data['created_by'] ?? null,
            );

            $this->commissionService->createForPayment($payment);

            $this->auditService->record(
                action: 'payment_registered',
                module: 'payments',
                companyId: (int) $loan->company_id,
                userId: $data['created_by'] ?? null,
                auditable: $payment,
                description: "Pago registrado al préstamo {$loan->loan_number}.",
                newValues: [
                    ...($payment->fresh()?->toArray() ?? []),
                    'allocation_mode' => $mode,
                    'target_installment_id' => $targetInstallmentId,
                    'excess_action' => $leftover > 0 ? $excessAction : null,
                    'capital_prepayment_amount' => $capitalPrepaid > 0 ? $capitalPrepaid : null,
                ],
            );

            return $payment->fresh(['details']) ?? $payment;
        });
    }

    public function findForCompany(int $companyId, int $paymentId): Payment
    {
        return Payment::query()
            ->with([
                'client:id,full_name,identification,phone',
                'loan:id,loan_number,principal_amount,total_amount,remaining_balance,status,currency',
                'collector:id,name',
                'cancelledBy:id,name',
                'details.installment:id,installment_number,due_date,interest_amount,paid_interest',
            ])
            ->forCompany($companyId)
            ->whereKey($paymentId)
            ->firstOrFail();
    }

    public function cancel(int $companyId, int $paymentId, int $cancelledBy, string $reason): Payment
    {
        return DB::transaction(function () use ($companyId, $paymentId, $cancelledBy, $reason): Payment {
            /** @var Payment $payment */
            $payment = Payment::query()
                ->with('details')
                ->forCompany($companyId)
                ->whereKey($paymentId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== 'valid') {
                throw new InvalidArgumentException('Este cobro ya fue anulado.');
            }

            $commission = $this->commissionForPayment($payment);
            if ($commission && $commission->status === 'paid') {
                throw new InvalidArgumentException('No se puede anular este cobro porque su comision ya fue pagada al cobrador.');
            }

            /** @var Loan $loan */
            $loan = Loan::query()
                ->with('company.settings')
                ->whereKey($payment->loan_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($loan->company->settings && ! $loan->company->settings->allow_payment_cancellation) {
                throw new InvalidArgumentException('La anulación de pagos está deshabilitada en la configuración.');
            }

            if ((float) $payment->capital_prepaid > 0) {
                throw new InvalidArgumentException('No se puede anular un cobro con abono a capital porque recalculó las cuotas. Ajusta el préstamo manualmente.');
            }

            foreach ($payment->details as $detail) {
                /** @var LoanInstallment $installment */
                $installment = LoanInstallment::query()
                    ->whereKey($detail->installment_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $installment->forceFill([
                    'paid_late_fee' => max(0, round((float) $installment->paid_late_fee - (float) $detail->late_fee_paid, 2)),
                    'paid_interest' => max(0, round((float) $installment->paid_interest - (float) $detail->interest_paid, 2)),
                    'paid_principal' => max(0, round((float) $installment->paid_principal - (float) $detail->principal_paid, 2)),
                    'total_paid' => max(0, round((float) $installment->total_paid - (float) $detail->amount_paid, 2)),
                ]);
                $installment->status = $this->resolveInstallmentStatus($installment);
                $installment->paid_at = $installment->status === 'paid' ? $installment->paid_at : null;
                $installment->save();
            }

            $newBalance = round((float) $loan->remaining_balance + (float) $payment->principal_paid, 2);

            $loan->forceFill([
                'paid_principal' => max(0, round((float) $loan->paid_principal - (float) $payment->principal_paid, 2)),
                'paid_interest' => max(0, round((float) $loan->paid_interest - (float) $payment->interest_paid, 2)),
                'paid_late_fee' => max(0, round((float) $loan->paid_late_fee - (float) $payment->late_fee_paid, 2)),
                'remaining_balance' => $newBalance,
                'status' => $loan->status === 'paid' ? 'active' : $loan->status,
                'end_date' => $loan->status === 'paid' ? null : $loan->end_date,
            ])->save();

            $payment->forceFill([
                'status' => 'cancelled',
                'cancelled_by' => $cancelledBy,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ])->save();

            $this->cancelCommission($payment);

            $this->cashMovementService->create(
                companyId: $companyId,
                type: 'adjustment',
                amount: (float) $payment->amount,
                direction: 'out',
                reference: $payment,
                description: "Anulación de cobro {$payment->receipt_number}: {$reason}",
                createdBy: $cancelledBy,
            );

            $this->auditService->record(
                action: 'payment_cancelled',
                module: 'payments',
                companyId: $companyId,
                userId: $cancelledBy,
                auditable: $payment,
                description: "Cobro anulado {$payment->receipt_number}.",
                newValues: $payment->fresh()?->toArray(),
            );

            return $payment->fresh(['details']) ?? $payment;
        });
    }

    /**
     * Reparte un monto único (pool) sobre las cuetas indicadas afectando solo las cubetas del modo.
     * Si $targetInstallmentId no es nulo, aplica solo a esa cuota.
     *
     * @param  Collection<int, LoanInstallment>  $installments
     * @param  list<string>  $buckets
     * @return array{principal:float, interest:float, late:float, leftover:float}
     */
    private function allocatePooled($payment, $installments, array $buckets, float $amount, ?int $targetInstallmentId): array
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('El monto del pago debe ser mayor que cero.');
        }

        if ($targetInstallmentId !== null) {
            $target = $installments->get($targetInstallmentId);
            if (! $target) {
                throw new InvalidArgumentException('La cuota seleccionada no está pendiente para este préstamo.');
            }
            $queue = [$target];
        } else {
            $queue = $installments->values()->all();
        }

        $totals = ['principal' => 0.0, 'interest' => 0.0, 'late' => 0.0];
        $remaining = $amount;

        foreach ($queue as $installment) {
            if ($remaining <= 0) {
                break;
            }

            $applied = $this->applyToInstallment($payment, $installment, $buckets, $remaining);
            $remaining = round($remaining - $applied['total'], 2);

            $totals['principal'] = round($totals['principal'] + $applied['principal'], 2);
            $totals['interest'] = round($totals['interest'] + $applied['interest'], 2);
            $totals['late'] = round($totals['late'] + $applied['late'], 2);
        }

        // El sobrante (overpago) lo decide register(): abono a capital o vuelto.
        $totals['leftover'] = max(0, $remaining);

        return $totals;
    }

    /**
     * Abono a capital: aplica un monto extra reduciendo el capital de las cuotas aún sin pagar
     * y re-amortiza esas cuotas (mismo método y cantidad, capital reducido). Devuelve el capital abonado.
     * Solo toca el calendario; los agregados del préstamo los actualiza register().
     */
    private function applyCapitalPrepayment(Loan $loan, float $amount, CarbonImmutable $date): float
    {
        $amount = round($amount, 2);

        $installments = $loan->installments()
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->where('total_paid', 0)
            ->orderBy('due_date')
            ->orderBy('installment_number')
            ->lockForUpdate()
            ->get();

        $reAmortizable = round((float) $installments->sum('principal_amount'), 2);

        if ($installments->isEmpty() || $reAmortizable <= 0) {
            return 0.0;
        }

        $applied = round(min($amount, $reAmortizable), 2);
        $newPrincipal = round($reAmortizable - $applied, 2);
        $count = $installments->count();

        if ($newPrincipal <= 0) {
            // Se abonó todo el capital restante: esas cuotas quedan saldadas.
            foreach ($installments as $installment) {
                $installment->forceFill([
                    'principal_amount' => 0,
                    'interest_amount' => 0,
                    'installment_amount' => 0,
                    'status' => 'paid',
                    'paid_at' => now(),
                ])->save();
            }

            return $applied;
        }

        $calculation = $this->calculator->calculate(
            principal: $newPrincipal,
            annualRate: (float) $loan->interest_rate,
            termQuantity: $count,
            method: (string) $loan->calculation_method,
        );

        foreach ($installments->values() as $index => $installment) {
            $row = $calculation['installments'][$index];
            $installment->forceFill([
                'principal_amount' => $row['principal'],
                'interest_amount' => $row['interest'],
                'installment_amount' => $row['amount'],
            ])->save();
            $this->lateFeeService->refreshInstallment($loan, $installment, $date);
        }

        return $applied;
    }

    /**
     * Reparto personalizado: lista de {installment_id, amount}, distribuye mora->interés->capital dentro de cada cuota.
     *
     * @param  Collection<int, LoanInstallment>  $installments
     * @param  array<int, array{installment_id:int|string, amount:float|string}>  $allocations
     * @return array{principal:float, interest:float, late:float}
     */
    private function allocateCustom($payment, $installments, array $allocations): array
    {
        $totals = ['principal' => 0.0, 'interest' => 0.0, 'late' => 0.0];
        $appliedAny = false;

        foreach ($allocations as $allocation) {
            $installmentId = (int) ($allocation['installment_id'] ?? 0);
            $cap = round((float) ($allocation['amount'] ?? 0), 2);

            if ($cap <= 0) {
                continue;
            }

            $installment = $installments->get($installmentId);
            if (! $installment) {
                throw new InvalidArgumentException('Una de las cuotas seleccionadas no está pendiente para este préstamo.');
            }

            $applied = $this->applyToInstallment($payment, $installment, ['late', 'interest', 'principal'], $cap);

            if (round($cap - $applied['total'], 2) > 0) {
                throw new InvalidArgumentException("El monto asignado a la cuota #{$installment->installment_number} excede lo que adeuda.");
            }

            $appliedAny = true;
            $totals['principal'] = round($totals['principal'] + $applied['principal'], 2);
            $totals['interest'] = round($totals['interest'] + $applied['interest'], 2);
            $totals['late'] = round($totals['late'] + $applied['late'], 2);
        }

        if (! $appliedAny) {
            throw new InvalidArgumentException('Selecciona al menos una cuota con un monto mayor que cero.');
        }

        return $totals;
    }

    /**
     * Aplica fondos a una cuota afectando solo las cubetas indicadas (en orden). Persiste cuota y detalle.
     *
     * @param  list<string>  $buckets
     * @return array{principal:float, interest:float, late:float, total:float}
     */
    private function applyToInstallment($payment, LoanInstallment $installment, array $buckets, float $available): array
    {
        $applied = ['late' => 0.0, 'interest' => 0.0, 'principal' => 0.0];

        foreach ($buckets as $bucket) {
            if ($available <= 0) {
                break;
            }

            $due = match ($bucket) {
                'late' => max(0, (float) $installment->late_fee - (float) $installment->paid_late_fee),
                'interest' => max(0, (float) $installment->interest_amount - (float) $installment->paid_interest),
                'principal' => max(0, (float) $installment->principal_amount - (float) $installment->paid_principal),
                default => 0.0,
            };

            [$amountApplied, $available] = $this->applyAmount($available, $due);
            $applied[$bucket] = $amountApplied;
        }

        $total = round($applied['late'] + $applied['interest'] + $applied['principal'], 2);

        if ($total <= 0) {
            return ['principal' => 0.0, 'interest' => 0.0, 'late' => 0.0, 'total' => 0.0];
        }

        $installment->forceFill([
            'paid_late_fee' => round((float) $installment->paid_late_fee + $applied['late'], 2),
            'paid_interest' => round((float) $installment->paid_interest + $applied['interest'], 2),
            'paid_principal' => round((float) $installment->paid_principal + $applied['principal'], 2),
            'total_paid' => round((float) $installment->total_paid + $total, 2),
        ]);
        $installment->status = $this->resolveInstallmentStatus($installment);
        $installment->paid_at = $installment->status === 'paid' ? now() : null;
        $installment->save();

        $payment->details()->create([
            'installment_id' => $installment->id,
            'principal_paid' => $applied['principal'],
            'interest_paid' => $applied['interest'],
            'late_fee_paid' => $applied['late'],
            'amount_paid' => $total,
        ]);

        return ['principal' => $applied['principal'], 'interest' => $applied['interest'], 'late' => $applied['late'], 'total' => $total];
    }

    /**
     * Cuotas pendientes de un préstamo con lo adeudado por cubeta a una fecha (para la UI de cobro).
     *
     * @return list<array{id:int, number:int, due_date:string, principal_due:float, interest_due:float, late_due:float, total_due:float, status:string}>
     */
    public function pendingInstallmentsFor(Loan $loan, ?CarbonImmutable $date = null): array
    {
        $date ??= CarbonImmutable::today();

        return $loan->installments()
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->orderBy('due_date')
            ->orderBy('installment_number')
            ->get()
            ->map(function (LoanInstallment $installment) use ($loan, $date): array {
                $this->lateFeeService->refreshInstallment($loan, $installment, $date);

                $principalDue = round(max(0, (float) $installment->principal_amount - (float) $installment->paid_principal), 2);
                $interestDue = round(max(0, (float) $installment->interest_amount - (float) $installment->paid_interest), 2);
                $lateDue = round(max(0, (float) $installment->late_fee - (float) $installment->paid_late_fee), 2);

                return [
                    'id' => (int) $installment->id,
                    'number' => (int) $installment->installment_number,
                    'due_date' => $installment->due_date->format('d/m/Y'),
                    'principal_due' => $principalDue,
                    'interest_due' => $interestDue,
                    'late_due' => $lateDue,
                    'total_due' => round($principalDue + $interestDue + $lateDue, 2),
                    'status' => $installment->status,
                ];
            })
            ->all();
    }

    /**
     * @return array{0:float,1:float}
     */
    private function applyAmount(float $available, float $due): array
    {
        $applied = min($available, $due);

        return [round($applied, 2), round($available - $applied, 2)];
    }

    private function resolveInstallmentStatus($installment): string
    {
        $principalDue = round((float) $installment->principal_amount - (float) $installment->paid_principal, 2);
        $interestDue = round((float) $installment->interest_amount - (float) $installment->paid_interest, 2);
        $lateDue = round((float) $installment->late_fee - (float) $installment->paid_late_fee, 2);

        if ($principalDue <= 0 && $interestDue <= 0 && $lateDue <= 0) {
            return 'paid';
        }

        return (float) $installment->total_paid > 0 ? 'partial' : ((int) $installment->days_late > 0 ? 'late' : 'pending');
    }

    private function receiptNumber(int $companyId): string
    {
        $prefix = CompanySetting::query()->where('company_id', $companyId)->value('receipt_prefix') ?: 'REC';

        return $prefix.'-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }

    private function cancelCommission(Payment $payment): void
    {
        $commission = $this->commissionForPayment($payment);

        if ($commission && $commission->status !== 'paid') {
            $commission->forceFill(['status' => 'cancelled'])->save();
        }
    }

    private function commissionForPayment(Payment $payment): ?CollectorCommission
    {
        $commission = $payment->collector_id
            ? $payment->collector?->commissions()->where('payment_id', $payment->id)->first()
            : null;

        if (! $commission) {
            $commission = CollectorCommission::query()
                ->where('payment_id', $payment->id)
                ->first();
        }

        return $commission;
    }
}

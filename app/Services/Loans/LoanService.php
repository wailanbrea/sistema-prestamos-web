<?php

declare(strict_types=1);

namespace App\Services\Loans;

use App\Models\CompanySetting;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanQuote;
use App\Services\Audit\AuditService;
use App\Services\Cash\CashMovementService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanService
{
    public function __construct(
        private readonly LoanCalculatorService $calculator,
        private readonly InstallmentGeneratorService $installmentGenerator,
        private readonly CashMovementService $cashMovementService,
        private readonly AuditService $auditService,
        private readonly LateFeeService $lateFeeService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForCompany(int $companyId, array $filters = []): LengthAwarePaginator
    {
        $today = now()->toDateString();
        $showAll = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $pendingBalanceSql = '(case when (installment_amount - paid_principal - paid_interest) > 0 then (installment_amount - paid_principal - paid_interest) else 0 end)';
        $pendingLateFeeSql = '(case when (late_fee - paid_late_fee) > 0 then (late_fee - paid_late_fee) else 0 end)';
        $hasPendingAmountSql = "({$pendingBalanceSql} + {$pendingLateFeeSql}) > 0";

        return Loan::query()
            ->with(['client', 'collector'])
            ->forCompany($companyId)
            ->select('loans.*')
            ->selectSub(function ($query) use ($today, $hasPendingAmountSql): void {
                $query->from('loan_installments')
                    ->selectRaw('count(*)')
                    ->whereColumn('loan_installments.loan_id', 'loans.id')
                    ->whereNotIn('status', ['paid', 'cancelled'])
                    ->whereDate('due_date', '<', $today)
                    ->whereRaw($hasPendingAmountSql);
            }, 'overdue_installments_count')
            ->selectSub(function ($query) use ($today, $pendingBalanceSql, $pendingLateFeeSql, $hasPendingAmountSql): void {
                $query->from('loan_installments')
                    ->selectRaw("coalesce(sum({$pendingBalanceSql} + {$pendingLateFeeSql}), 0)")
                    ->whereColumn('loan_installments.loan_id', 'loans.id')
                    ->whereNotIn('status', ['paid', 'cancelled'])
                    ->whereDate('due_date', '<', $today)
                    ->whereRaw($hasPendingAmountSql);
            }, 'overdue_amount_due')
            ->selectSub(function ($query) use ($today, $pendingBalanceSql, $pendingLateFeeSql, $hasPendingAmountSql): void {
                $query->from('loan_installments')
                    ->selectRaw("coalesce(sum({$pendingBalanceSql} + {$pendingLateFeeSql}), 0)")
                    ->whereColumn('loan_installments.loan_id', 'loans.id')
                    ->whereNotIn('status', ['paid', 'cancelled'])
                    ->whereDate('due_date', '<=', $today)
                    ->whereRaw($hasPendingAmountSql);
            }, 'amount_due_today')
            ->tap(fn (Builder $query) => $this->applyFilters($query, $filters))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Resumen de cartera para las tarjetas de la pantalla de préstamos.
     * Respeta los mismos filtros (estado/cliente) que el listado.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function summaryForCompany(int $companyId, array $filters = []): array
    {
        $row = Loan::query()
            ->forCompany($companyId)
            ->tap(fn (Builder $query) => $this->applyFilters($query, $filters))
            ->selectRaw('count(*) as total')
            ->selectRaw("coalesce(sum(case when status in ('active', 'late') then 1 else 0 end), 0) as outstanding")
            ->selectRaw("coalesce(sum(case when status = 'late' then 1 else 0 end), 0) as late")
            ->selectRaw("coalesce(sum(case when status = 'pending' then 1 else 0 end), 0) as pending")
            ->selectRaw("coalesce(sum(case when status = 'paid' then 1 else 0 end), 0) as paid")
            ->selectRaw('coalesce(sum(principal_amount), 0) as principal_total')
            ->selectRaw('coalesce(sum(remaining_balance), 0) as balance_total')
            ->first();

        // Cuotas vencidas y mora pendiente sobre los mismos préstamos filtrados.
        $today = now()->toDateString();
        $pendingBalanceSql = '(case when (installment_amount - paid_principal - paid_interest) > 0 then (installment_amount - paid_principal - paid_interest) else 0 end)';
        $pendingLateFeeSql = '(case when (late_fee - paid_late_fee) > 0 then (late_fee - paid_late_fee) else 0 end)';

        $installments = LoanInstallment::query()
            ->whereHas('loan', function (Builder $query) use ($companyId, $filters): void {
                $query->forCompany($companyId);
                $this->applyFilters($query, $filters);
            })
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->whereDate('due_date', '<', $today)
            ->whereRaw("({$pendingBalanceSql} + {$pendingLateFeeSql}) > 0")
            ->selectRaw('count(*) as overdue_count')
            ->selectRaw("coalesce(sum({$pendingLateFeeSql}), 0) as late_fee_pending")
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'outstanding' => (int) ($row->outstanding ?? 0),
            'late' => (int) ($row->late ?? 0),
            'pending' => (int) ($row->pending ?? 0),
            'paid' => (int) ($row->paid ?? 0),
            'principal_total' => (float) ($row->principal_total ?? 0),
            'balance_total' => (float) ($row->balance_total ?? 0),
            'overdue_installments' => (int) ($installments->overdue_count ?? 0),
            'late_fee_pending' => (float) ($installments->late_fee_pending ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $showAll = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $query
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(empty($filters['status']) && ! $showAll, fn (Builder $query) => $query->whereIn('status', ['active', 'late']))
            ->when($filters['client_id'] ?? null, fn (Builder $query, string $clientId) => $query->where('client_id', $clientId))
            ->when(trim((string) ($filters['q'] ?? '')) !== '', function (Builder $query) use ($filters): void {
                $term = trim((string) $filters['q']);
                $like = "%{$term}%";

                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->where('loan_number', 'like', $like)
                        ->orWhereHas('client', function (Builder $clientQuery) use ($like): void {
                            $clientQuery
                                ->where('full_name', 'like', $like)
                                ->orWhere('phone', 'like', $like)
                                ->orWhere('identification', 'like', $like);
                        });
                });
            });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $companyId, ?int $userId, array $data): Loan
    {
        return DB::transaction(function () use ($companyId, $userId, $data): Loan {
            $quote = null;

            if (! empty($data['quote_id'])) {
                $quote = LoanQuote::query()
                    ->forCompany($companyId)
                    ->whereKey((int) $data['quote_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($quote->status === 'converted') {
                    throw new InvalidArgumentException('Esta cotización ya fue convertida en préstamo.');
                }

                $data = $this->mergeQuoteData($data, $quote);
            }

            $calculation = $this->calculator->calculate(
                principal: (float) $data['principal_amount'],
                annualRate: (float) $data['interest_rate'],
                termQuantity: (int) $data['term_quantity'],
                method: (string) $data['calculation_method'],
            );

            $settings = CompanySetting::query()->where('company_id', $companyId)->first();

            // Si se exige contrato firmado, el préstamo pasa por aprobación para que
            // el desembolso solo ocurra tras la firma (se valida en approve()).
            $contractRequired = (bool) ($data['contract_required'] ?? ($settings?->require_signed_contract_for_disbursement ?? false));
            $requiresApproval = (bool) ($settings?->require_approval_for_loans ?? false) || $contractRequired;

            $loan = Loan::query()->create([
                'company_id' => $companyId,
                'client_id' => $data['client_id'],
                'currency' => $data['currency'] ?? (CompanySetting::query()->where('company_id', $companyId)->value('default_loan_currency') ?: 'RD$'),
                'collector_id' => $data['collector_id'] ?? null,
                'quote_id' => $quote?->id,
                'loan_number' => $this->nextLoanNumber($companyId, $settings?->loan_prefix ?: 'PRE'),
                'principal_amount' => $data['principal_amount'],
                'interest_rate' => $data['interest_rate'],
                'interest_type' => $data['interest_type'],
                'payment_frequency' => $data['payment_frequency'],
                'calculation_method' => $data['calculation_method'],
                'term_quantity' => $data['term_quantity'],
                'installment_amount' => $calculation['installment_amount'],
                'total_interest' => $calculation['total_interest'],
                'total_amount' => $calculation['total_amount'],
                'remaining_balance' => $data['principal_amount'],
                'late_fee_type' => $data['late_fee_type'],
                'late_fee_value' => $data['late_fee_value'] ?? 0,
                'allows_capital_prepayment' => (bool) ($data['allows_capital_prepayment'] ?? true),
                'start_date' => $data['start_date'],
                'first_payment_date' => $data['first_payment_date'],
                'status' => $requiresApproval ? 'pending' : 'active',
                'contract_required' => $contractRequired,
                'guarantee_description' => $data['guarantee_description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'approved_by' => $requiresApproval ? null : $userId,
                'created_by' => $userId,
            ]);

            $this->installmentGenerator->createForLoan($loan, $calculation, (bool) ($settings?->exclude_sundays_for_daily_loans ?? false));

            // El desembolso en caja solo ocurre al aprobar (o de inmediato si no requiere aprobación).
            if (! $requiresApproval) {
                $this->cashMovementService->create(
                    companyId: $companyId,
                    type: 'loan_disbursement',
                    amount: (float) $loan->principal_amount,
                    direction: 'out',
                    reference: $loan,
                    description: "Desembolso de préstamo {$loan->loan_number}",
                    createdBy: $userId,
                );
            }

            if ($quote) {
                $quote->forceFill(['status' => 'converted'])->save();
            }

            $this->auditService->record(
                action: 'loan_created',
                module: 'loans',
                companyId: $companyId,
                userId: $userId,
                auditable: $loan,
                description: "Préstamo {$loan->loan_number} creado.",
                newValues: $loan->fresh()?->toArray(),
            );

            return $loan->fresh(['client', 'collector', 'installments']) ?? $loan;
        });
    }

    public function findForCompany(int $companyId, int $loanId): Loan
    {
        return Loan::query()
            ->with([
                'client',
                'collector',
                'quote',
                'installments' => fn ($query) => $query->orderBy('installment_number'),
                'payments' => fn ($query) => $query
                    ->with(['collector:id,name', 'targetInstallment:id,installment_number', 'details.installment:id,installment_number,due_date'])
                    ->orderByDesc('payment_date')
                    ->orderByDesc('id'),
            ])
            ->forCompany($companyId)
            ->whereKey($loanId)
            ->firstOrFail();
    }

    /**
     * Aprueba un préstamo pendiente: lo activa y registra el desembolso en caja.
     */
    public function approve(int $companyId, ?int $userId, Loan $loan): Loan
    {
        return DB::transaction(function () use ($companyId, $userId, $loan): Loan {
            $loan = Loan::query()->forCompany($companyId)->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($loan->status !== 'pending') {
                throw new InvalidArgumentException('Solo se pueden aprobar préstamos pendientes.');
            }

            // Bloqueo de desembolso: si el préstamo exige contrato firmado y aún no
            // lo está, no se aprueba ni se registra el desembolso en caja.
            if ($loan->contract_required && ! $loan->contract_signed) {
                throw new InvalidArgumentException('No se puede desembolsar: el contrato no ha sido firmado por el cliente.');
            }

            $loan->forceFill([
                'status' => 'active',
                'approved_by' => $userId,
            ])->save();

            $this->cashMovementService->create(
                companyId: $companyId,
                type: 'loan_disbursement',
                amount: (float) $loan->principal_amount,
                direction: 'out',
                reference: $loan,
                description: "Desembolso de préstamo {$loan->loan_number}",
                createdBy: $userId,
            );

            $this->auditService->record(
                action: 'loan_approved',
                module: 'loans',
                companyId: $companyId,
                userId: $userId,
                auditable: $loan,
                description: "Préstamo {$loan->loan_number} aprobado.",
                newValues: $loan->fresh()?->toArray(),
            );

            return $loan->fresh(['client', 'collector', 'installments']) ?? $loan;
        });
    }

    /**
     * Rechaza un préstamo pendiente: lo marca como cancelado (no hubo desembolso).
     */
    public function reject(int $companyId, ?int $userId, Loan $loan, ?string $reason = null): Loan
    {
        return DB::transaction(function () use ($companyId, $userId, $loan, $reason): Loan {
            $loan = Loan::query()->forCompany($companyId)->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($loan->status !== 'pending') {
                throw new InvalidArgumentException('Solo se pueden rechazar préstamos pendientes.');
            }

            $loan->forceFill([
                'status' => 'cancelled',
                'notes' => trim((string) $loan->notes."\n".'Rechazado: '.($reason ?: 'sin motivo')),
            ])->save();

            $this->auditService->record(
                action: 'loan_rejected',
                module: 'loans',
                companyId: $companyId,
                userId: $userId,
                auditable: $loan,
                description: "Préstamo {$loan->loan_number} rechazado.",
                newValues: $loan->fresh()?->toArray(),
            );

            return $loan->fresh(['client', 'collector', 'installments']) ?? $loan;
        });
    }

    /**
     * Actualiza un préstamo. Los campos no contables (cobrador, garantía, notas) se aplican siempre.
     * Las condiciones financieras solo si el préstamo no tiene pagos válidos (recalcula y regenera cuotas).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $companyId, ?int $userId, Loan $loan, array $data): Loan
    {
        return DB::transaction(function () use ($companyId, $userId, $loan, $data): Loan {
            $loan = Loan::query()->forCompany($companyId)->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            $original = $loan->only(['principal_amount', 'interest_rate', 'term_quantity', 'calculation_method', 'payment_frequency']);

            // Campos siempre editables.
            $loan->fill([
                'collector_id' => $data['collector_id'] ?? null,
                'currency' => $data['currency'] ?? $loan->currency,
                'guarantee_description' => $data['guarantee_description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'allows_capital_prepayment' => (bool) ($data['allows_capital_prepayment'] ?? false),
            ]);

            // Mora siempre editable: no recalcula cuotas, solo afecta cobros futuros.
            if (array_key_exists('late_fee_type', $data) && $data['late_fee_type'] !== null) {
                $loan->late_fee_type = $data['late_fee_type'];
                $loan->late_fee_value = $data['late_fee_value'] ?? 0;
            }

            if (array_key_exists('principal_amount', $data) && $data['principal_amount'] !== null) {
                $this->recalculateScheduleAfterPayments($companyId, $loan, $data);
            } else {
                $loan->save();
            }

            $this->auditService->record(
                action: 'loan_updated',
                module: 'loans',
                companyId: $companyId,
                userId: $userId,
                auditable: $loan,
                description: "Préstamo {$loan->loan_number} actualizado.",
                oldValues: $original,
                newValues: $loan->fresh()?->toArray(),
            );

            return $loan->fresh(['client', 'collector', 'installments']) ?? $loan;
        });
    }

    /**
     * Recalcula el plan conservando las cuotas que ya tienen pagos aplicados.
     *
     * @param  array<string, mixed>  $data
     */
    private function recalculateScheduleAfterPayments(int $companyId, Loan $loan, array $data): void
    {
        $settings = CompanySetting::query()->where('company_id', $companyId)->first();
        $excludeSundays = (bool) ($settings?->exclude_sundays_for_daily_loans ?? false);

        $paidPrincipal = round((float) $loan->paid_principal, 2);
        $paidInterest = round((float) $loan->paid_interest, 2);
        $newPrincipal = round((float) $data['principal_amount'], 2);
        $newTermQuantity = (int) $data['term_quantity'];

        if ($newPrincipal < $paidPrincipal) {
            throw new InvalidArgumentException('El nuevo monto principal no puede ser menor al capital ya cobrado.');
        }

        $protectedInstallments = $loan->installments()
            ->where('total_paid', '>', 0)
            ->orderBy('installment_number')
            ->lockForUpdate()
            ->get();
        $protectedCount = $protectedInstallments->count();
        $remainingTermQuantity = $newTermQuantity - $protectedCount;
        $remainingPrincipal = round($newPrincipal - $paidPrincipal, 2);

        if ($remainingPrincipal > 0 && $remainingTermQuantity <= 0) {
            throw new InvalidArgumentException('El plazo debe dejar cuotas futuras para el capital pendiente.');
        }

        $calculation = $remainingPrincipal > 0
            ? $this->calculator->calculate(
                principal: $remainingPrincipal,
                annualRate: (float) $data['interest_rate'],
                termQuantity: $remainingTermQuantity,
                method: (string) $data['calculation_method'],
            )
            : [
                'installment_amount' => 0.0,
                'total_interest' => 0.0,
                'total_amount' => 0.0,
                'installments' => [],
            ];

        $loan->fill([
            'principal_amount' => $newPrincipal,
            'interest_rate' => $data['interest_rate'],
            'interest_type' => $data['interest_type'],
            'payment_frequency' => $data['payment_frequency'],
            'calculation_method' => $data['calculation_method'],
            'term_quantity' => $newTermQuantity,
            'installment_amount' => $calculation['installment_amount'],
            'total_interest' => round($paidInterest + (float) $calculation['total_interest'], 2),
            'total_amount' => round($newPrincipal + $paidInterest + (float) $calculation['total_interest'], 2),
            'remaining_balance' => $remainingPrincipal,
            'late_fee_type' => $data['late_fee_type'] ?? $loan->late_fee_type,
            'late_fee_value' => $data['late_fee_value'] ?? $loan->late_fee_value,
            'start_date' => $data['start_date'],
            'first_payment_date' => $data['first_payment_date'],
            'status' => $loan->status === 'paid' && $remainingPrincipal > 0 ? 'active' : $loan->status,
        ]);
        $loan->save();

        $firstFutureDate = $this->firstFutureDueDate(
            loan: $loan,
            firstPaymentDate: (string) $data['first_payment_date'],
            frequency: (string) $data['payment_frequency'],
            protectedCount: $protectedCount,
            excludeSundays: $excludeSundays,
        );

        $loan->installments()
            ->where('total_paid', '<=', 0)
            ->delete();

        $futureDates = $this->installmentGenerator->dueDatesFor($firstFutureDate, (string) $data['payment_frequency'], count($calculation['installments']), $excludeSundays);

        foreach ($calculation['installments'] as $index => $installment) {
            $loan->installments()->create([
                'installment_number' => $protectedCount + (int) $installment['number'],
                'due_date' => $futureDates[$index],
                'principal_amount' => $installment['principal'],
                'interest_amount' => $installment['interest'],
                'installment_amount' => $installment['amount'],
            ]);
        }

        foreach ($loan->installments()->whereNotIn('status', ['paid', 'cancelled'])->get() as $installment) {
            $this->lateFeeService->refreshInstallment($loan, $installment);
        }

        $loan->forceFill(['status' => $this->resolveStatusFromInstallments($loan)])->save();
    }

    private function resolveStatusFromInstallments(Loan $loan): string
    {
        $openInstallments = $loan->installments()
            ->where('status', '!=', 'cancelled')
            ->get(['status', 'principal_amount', 'paid_principal', 'interest_amount', 'paid_interest', 'late_fee', 'paid_late_fee']);

        $hasPendingDebt = $openInstallments->contains(function ($installment): bool {
            $pendingPrincipal = max(0, round((float) $installment->principal_amount - (float) $installment->paid_principal, 2));
            $pendingInterest = max(0, round((float) $installment->interest_amount - (float) $installment->paid_interest, 2));
            $pendingLateFee = max(0, round((float) $installment->late_fee - (float) $installment->paid_late_fee, 2));

            return ($pendingPrincipal + $pendingInterest + $pendingLateFee) > 0;
        });

        if (! $hasPendingDebt) {
            return 'paid';
        }

        return $openInstallments->where('status', 'late')->isNotEmpty() ? 'late' : 'active';
    }

    private function firstFutureDueDate(Loan $loan, string $firstPaymentDate, string $frequency, int $protectedCount, bool $excludeSundays): string
    {
        $existingFuture = $loan->installments()
            ->where('total_paid', '<=', 0)
            ->orderBy('installment_number')
            ->value('due_date');

        if ($existingFuture) {
            return CarbonImmutable::parse($existingFuture)->toDateString();
        }

        $dates = $this->installmentGenerator->dueDatesFor($firstPaymentDate, $frequency, $protectedCount + 1, $excludeSundays);

        return $dates[$protectedCount] ?? $firstPaymentDate;
    }

    public function updateLateFee(int $companyId, ?int $userId, Loan $loan, string $type, float $value): Loan
    {
        return DB::transaction(function () use ($companyId, $userId, $loan, $type, $value): Loan {
            $loan = Loan::query()
                ->forCompany($companyId)
                ->whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldValues = [
                'late_fee_type' => $loan->late_fee_type,
                'late_fee_value' => (float) $loan->late_fee_value,
                'pending_late_fee' => (float) $loan->installments()
                    ->whereNotIn('status', ['paid', 'cancelled'])
                    ->get(['late_fee', 'paid_late_fee'])
                    ->sum(fn ($installment): float => max(0, (float) $installment->late_fee - (float) $installment->paid_late_fee)),
            ];

            $loan->forceFill([
                'late_fee_type' => $type,
                'late_fee_value' => $type === 'none' ? 0 : $value,
            ])->save();

            $installments = $loan->installments()
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->orderBy('installment_number')
                ->lockForUpdate()
                ->get();

            foreach ($installments as $installment) {
                $this->lateFeeService->refreshInstallment($loan, $installment);
            }

            $hasLateInstallments = $loan->installments()->where('status', 'late')->exists();

            if ($hasLateInstallments && $loan->status === 'active') {
                $loan->forceFill(['status' => 'late'])->save();
            }

            if (! $hasLateInstallments && $loan->status === 'late') {
                $loan->forceFill(['status' => 'active'])->save();
            }

            $loan->refresh();

            $newValues = [
                'late_fee_type' => $loan->late_fee_type,
                'late_fee_value' => (float) $loan->late_fee_value,
                'pending_late_fee' => (float) $loan->installments()
                    ->whereNotIn('status', ['paid', 'cancelled'])
                    ->get(['late_fee', 'paid_late_fee'])
                    ->sum(fn ($installment): float => max(0, (float) $installment->late_fee - (float) $installment->paid_late_fee)),
            ];

            $this->auditService->record(
                action: 'loan_late_fee_updated',
                module: 'loans',
                companyId: $companyId,
                userId: $userId,
                auditable: $loan,
                description: "Mora del prestamo {$loan->loan_number} actualizada.",
                oldValues: $oldValues,
                newValues: $newValues,
            );

            return $loan->fresh(['client', 'collector', 'installments']) ?? $loan;
        });
    }

    public function waiveInstallmentLateFee(int $companyId, ?int $userId, Loan $loan, LoanInstallment $installment, ?string $reason = null): LoanInstallment
    {
        return DB::transaction(function () use ($companyId, $userId, $loan, $installment, $reason): LoanInstallment {
            $loan = Loan::query()
                ->forCompany($companyId)
                ->whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $installment = LoanInstallment::query()
                ->where('loan_id', $loan->id)
                ->whereKey($installment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($installment->status, ['paid', 'cancelled'], true)) {
                throw new InvalidArgumentException('No se puede quitar mora de una cuota saldada o cancelada.');
            }

            $pendingLateFee = max(0, round((float) $installment->late_fee - (float) $installment->paid_late_fee, 2));
            if ($pendingLateFee <= 0) {
                throw new InvalidArgumentException('Esta cuota no tiene mora pendiente.');
            }

            $oldValues = [
                'installment_id' => $installment->id,
                'installment_number' => (int) $installment->installment_number,
                'late_fee' => (float) $installment->late_fee,
                'paid_late_fee' => (float) $installment->paid_late_fee,
                'pending_late_fee' => $pendingLateFee,
                'status' => $installment->status,
            ];

            $installment->forceFill([
                'late_fee' => round((float) $installment->paid_late_fee, 2),
                'late_fee_waived_at' => now(),
                'late_fee_waived_by' => $userId,
                'late_fee_waived_reason' => $reason,
            ])->save();

            $installment->forceFill([
                'status' => $this->resolveInstallmentStatus($installment->fresh() ?? $installment),
            ])->save();

            $loan->forceFill(['status' => $this->resolveStatusFromInstallments($loan)])->save();

            $freshInstallment = $installment->fresh(['loan.client']) ?? $installment;

            $this->auditService->record(
                action: 'loan_installment_late_fee_waived',
                module: 'loans',
                companyId: $companyId,
                userId: $userId,
                auditable: $freshInstallment,
                description: "Mora de la cuota #{$freshInstallment->installment_number} del prestamo {$loan->loan_number} eliminada.",
                oldValues: $oldValues,
                newValues: [
                    'installment_id' => $freshInstallment->id,
                    'installment_number' => (int) $freshInstallment->installment_number,
                    'late_fee' => (float) $freshInstallment->late_fee,
                    'paid_late_fee' => (float) $freshInstallment->paid_late_fee,
                    'pending_late_fee' => 0,
                    'status' => $freshInstallment->status,
                    'reason' => $reason,
                ],
            );

            return $freshInstallment;
        });
    }

    private function resolveInstallmentStatus(LoanInstallment $installment): string
    {
        if ($installment->status === 'cancelled') {
            return 'cancelled';
        }

        $pendingPrincipal = max(0, round((float) $installment->principal_amount - (float) $installment->paid_principal, 2));
        $pendingInterest = max(0, round((float) $installment->interest_amount - (float) $installment->paid_interest, 2));
        $pendingLateFee = max(0, round((float) $installment->late_fee - (float) $installment->paid_late_fee, 2));

        if (($pendingPrincipal + $pendingInterest + $pendingLateFee) <= 0) {
            return 'paid';
        }

        if ($installment->due_date !== null && $installment->due_date->startOfDay()->lt(now()->startOfDay())) {
            return 'late';
        }

        return (float) $installment->total_paid > 0 ? 'partial' : 'pending';
    }

    /**
     * Anula (soft delete) un préstamo sin pagos válidos, revirtiendo el desembolso en caja.
     */
    public function delete(int $companyId, ?int $userId, Loan $loan): void
    {
        DB::transaction(function () use ($companyId, $userId, $loan): void {
            $loan = Loan::query()->forCompany($companyId)->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($loan->payments()->where('status', 'valid')->exists()) {
                throw new InvalidArgumentException('No se puede eliminar un préstamo con pagos registrados. Anula los pagos primero.');
            }

            $this->cashMovementService->create(
                companyId: $companyId,
                type: 'adjustment',
                amount: (float) $loan->principal_amount,
                direction: 'in',
                reference: $loan,
                description: "Reverso por anulación de préstamo {$loan->loan_number}",
                createdBy: $userId,
            );

            $this->auditService->record(
                action: 'loan_deleted',
                module: 'loans',
                companyId: $companyId,
                userId: $userId,
                auditable: $loan,
                description: "Préstamo {$loan->loan_number} anulado.",
                oldValues: $loan->toArray(),
            );

            $loan->installments()->delete();
            $loan->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeQuoteData(array $data, LoanQuote $quote): array
    {
        return array_merge($data, [
            'client_id' => $data['client_id'] ?? $quote->client_id,
            'principal_amount' => $quote->amount,
            'interest_rate' => $quote->interest_rate,
            'interest_type' => $quote->interest_type,
            'payment_frequency' => $quote->payment_frequency,
            'calculation_method' => $quote->calculation_method,
            'term_quantity' => $quote->term_quantity,
            'start_date' => $data['start_date'] ?? $quote->start_date?->toDateString(),
            'first_payment_date' => $data['first_payment_date'] ?? $quote->first_payment_date?->toDateString(),
        ]);
    }

    private function nextLoanNumber(int $companyId, string $prefix = 'PRE'): string
    {
        $nextId = (int) Loan::query()->forCompany($companyId)->withTrashed()->count() + 1;

        return $prefix.'-'.now()->format('Ymd').'-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\CollectorCommission;
use App\Models\Payment;
use App\Services\Audit\AuditService;
use App\Services\Cash\CashMovementService;
use App\Services\Collectors\CollectorCommissionService;
use App\Services\Loans\LateFeeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
    ) {
    }

    /**
     * @param array<string, mixed> $filters
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
     * @param array{loan_id:int, amount:float, payment_date:string, payment_method:string, collector_id?:int|null, created_by?:int|null} $data
     */
    public function register(array $data): Payment
    {
        if (($data['amount'] ?? 0) <= 0) {
            throw new InvalidArgumentException('El monto del pago debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($data): Payment {
            /** @var Loan $loan */
            $loan = Loan::query()
                ->whereKey($data['loan_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($loan->status, ['active', 'late'], true)) {
                throw new InvalidArgumentException('Solo se pueden registrar pagos a préstamos activos o atrasados.');
            }

            $remainingPayment = round((float) $data['amount'], 2);
            $principalPaid = 0.0;
            $interestPaid = 0.0;
            $lateFeePaid = 0.0;
            $previousBalance = (float) $loan->remaining_balance;

            $payment = Payment::query()->create([
                'company_id' => $loan->company_id,
                'loan_id' => $loan->id,
                'client_id' => $loan->client_id,
                'collector_id' => $data['collector_id'] ?? $loan->collector_id,
                'receipt_number' => $this->receiptNumber(),
                'payment_date' => $data['payment_date'],
                'amount' => $remainingPayment,
                'payment_method' => $data['payment_method'],
                'previous_balance' => $previousBalance,
                'new_balance' => $previousBalance,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $installments = $loan->installments()
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->orderBy('due_date')
                ->orderBy('installment_number')
                ->lockForUpdate()
                ->get();

            foreach ($installments as $installment) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $this->lateFeeService->refreshInstallment($loan, $installment);

                $lateFeeDue = max(0, (float) $installment->late_fee - (float) $installment->paid_late_fee);
                $interestDue = max(0, (float) $installment->interest_amount - (float) $installment->paid_interest);
                $principalDue = max(0, (float) $installment->principal_amount - (float) $installment->paid_principal);

                [$lateApplied, $remainingPayment] = $this->applyAmount($remainingPayment, $lateFeeDue);
                [$interestApplied, $remainingPayment] = $this->applyAmount($remainingPayment, $interestDue);
                [$principalApplied, $remainingPayment] = $this->applyAmount($remainingPayment, $principalDue);

                $amountApplied = round($lateApplied + $interestApplied + $principalApplied, 2);

                if ($amountApplied <= 0) {
                    continue;
                }

                $installment->forceFill([
                    'paid_late_fee' => round((float) $installment->paid_late_fee + $lateApplied, 2),
                    'paid_interest' => round((float) $installment->paid_interest + $interestApplied, 2),
                    'paid_principal' => round((float) $installment->paid_principal + $principalApplied, 2),
                    'total_paid' => round((float) $installment->total_paid + $amountApplied, 2),
                ]);

                $installment->status = $this->resolveInstallmentStatus($installment);
                $installment->paid_at = $installment->status === 'paid' ? now() : null;
                $installment->save();

                $payment->details()->create([
                    'installment_id' => $installment->id,
                    'principal_paid' => $principalApplied,
                    'interest_paid' => $interestApplied,
                    'late_fee_paid' => $lateApplied,
                    'amount_paid' => $amountApplied,
                ]);

                $principalPaid = round($principalPaid + $principalApplied, 2);
                $interestPaid = round($interestPaid + $interestApplied, 2);
                $lateFeePaid = round($lateFeePaid + $lateApplied, 2);
            }

            if ($remainingPayment > 0) {
                throw new InvalidArgumentException('El monto excede el saldo pendiente del préstamo.');
            }

            $newBalance = max(0, round($previousBalance - $principalPaid, 2));

            $loan->forceFill([
                'paid_principal' => round((float) $loan->paid_principal + $principalPaid, 2),
                'paid_interest' => round((float) $loan->paid_interest + $interestPaid, 2),
                'paid_late_fee' => round((float) $loan->paid_late_fee + $lateFeePaid, 2),
                'remaining_balance' => $newBalance,
                'status' => $newBalance <= 0 ? 'paid' : $loan->status,
                'end_date' => $newBalance <= 0 ? now()->toDateString() : $loan->end_date,
            ])->save();

            $payment->forceFill([
                'principal_paid' => $principalPaid,
                'interest_paid' => $interestPaid,
                'late_fee_paid' => $lateFeePaid,
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
                newValues: $payment->fresh()?->toArray(),
            );

            return $payment->fresh(['details']) ?? $payment;
        });
    }

    public function findForCompany(int $companyId, int $paymentId): Payment
    {
        return Payment::query()
            ->with([
                'client:id,full_name,identification,phone',
                'loan:id,loan_number,principal_amount,total_amount,remaining_balance,status',
                'collector:id,name',
                'cancelledBy:id,name',
                'details.installment:id,installment_number,due_date',
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

            /** @var Loan $loan */
            $loan = Loan::query()
                ->with('company.settings')
                ->whereKey($payment->loan_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($loan->company->settings && ! $loan->company->settings->allow_payment_cancellation) {
                throw new InvalidArgumentException('La anulación de pagos está deshabilitada en la configuración.');
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

    private function receiptNumber(): string
    {
        return 'REC-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }

    private function cancelCommission(Payment $payment): void
    {
        $commission = $payment->collector_id
            ? $payment->collector?->commissions()->where('payment_id', $payment->id)->first()
            : null;

        if (! $commission) {
            $commission = CollectorCommission::query()
                ->where('payment_id', $payment->id)
                ->first();
        }

        if ($commission && $commission->status !== 'paid') {
            $commission->forceFill(['status' => 'cancelled'])->save();
        }
    }
}

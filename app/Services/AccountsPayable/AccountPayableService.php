<?php

declare(strict_types=1);

namespace App\Services\AccountsPayable;

use App\Models\AccountPayable;
use App\Models\AccountPayableInstallment;
use App\Models\AccountPayablePayment;
use App\Models\CashMovement;
use App\Models\CompanySetting;
use App\Services\Audit\AuditService;
use App\Services\Cash\CashMovementService;
use App\Services\Loans\LoanCalculatorService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AccountPayableService
{
    public function __construct(
        private readonly LoanCalculatorService $calculator,
        private readonly CashMovementService $cashMovementService,
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function paginateForCompany(int $companyId, array $filters = []): LengthAwarePaginator
    {
        return AccountPayable::query()
            ->with('creditor:id,name,phone')
            ->withCount('payments')
            ->forCompany($companyId)
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('reference', 'like', "%{$search}%")
                        ->orWhereHas('creditor', fn (Builder $creditor) => $creditor->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['creditor_id'] ?? null, fn (Builder $query, string $creditorId) => $query->where('creditor_id', $creditorId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('disbursement_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $companyId, array $data, int $createdBy): AccountPayable
    {
        return DB::transaction(function () use ($companyId, $data, $createdBy): AccountPayable {
            $calculation = $this->calculator->calculate(
                principal: (float) $data['principal_amount'],
                annualRate: (float) $data['interest_rate'],
                termQuantity: (int) $data['term_quantity'],
                method: (string) $data['calculation_method'],
            );

            $account = AccountPayable::query()->create([
                'company_id' => $companyId,
                'creditor_id' => $data['creditor_id'],
                'reference' => $this->referenceNumber($companyId),
                'currency' => $data['currency'] ?? (CompanySetting::query()->where('company_id', $companyId)->value('default_account_payable_currency') ?: 'RD$'),
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
                'late_fee_value' => $data['late_fee_value'],
                'disbursement_date' => $data['disbursement_date'],
                'first_payment_date' => $data['first_payment_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $createdBy,
            ]);

            $this->createInstallments($account, $calculation);

            $this->cashMovementService->create(
                companyId: $companyId,
                type: 'accounts_payable_disbursement',
                amount: (float) $account->principal_amount,
                direction: 'in',
                reference: $account,
                description: "Préstamo tomado {$account->reference}",
                createdBy: $createdBy,
                movementDate: $account->disbursement_date->toDateString(),
            );

            $this->auditService->record(
                action: 'account_payable_created',
                module: 'accounts_payable',
                companyId: $companyId,
                userId: $createdBy,
                auditable: $account,
                description: "Cuenta por pagar creada {$account->reference}.",
                newValues: $account->toArray(),
            );

            return $account->fresh(['creditor', 'installments']) ?? $account;
        });
    }

    public function findForCompany(int $companyId, int $accountId): AccountPayable
    {
        return AccountPayable::query()
            ->with([
                'creditor',
                'installments' => fn ($query) => $query->orderBy('installment_number'),
                'payments' => fn ($query) => $query->latest('payment_date')->latest('id'),
            ])
            ->forCompany($companyId)
            ->whereKey($accountId)
            ->firstOrFail();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $companyId, int $accountId, array $data, int $updatedBy): AccountPayable
    {
        return DB::transaction(function () use ($companyId, $accountId, $data, $updatedBy): AccountPayable {
            /** @var AccountPayable $account */
            $account = AccountPayable::query()
                ->forCompany($companyId)
                ->whereKey($accountId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($account->payments()->exists()) {
                throw new InvalidArgumentException('No puedes editar una cuenta por pagar que ya tiene pagos registrados.');
            }

            $calculation = $this->calculator->calculate(
                principal: (float) $data['principal_amount'],
                annualRate: (float) $data['interest_rate'],
                termQuantity: (int) $data['term_quantity'],
                method: (string) $data['calculation_method'],
            );

            $oldValues = $account->fresh(['installments'])?->toArray() ?? $account->toArray();

            $account->forceFill([
                'creditor_id' => $data['creditor_id'],
                'currency' => $data['currency'] ?? $account->currency,
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
                'late_fee_value' => $data['late_fee_value'],
                'disbursement_date' => $data['disbursement_date'],
                'first_payment_date' => $data['first_payment_date'],
                'notes' => $data['notes'] ?? null,
                'paid_principal' => 0,
                'paid_interest' => 0,
                'paid_late_fee' => 0,
                'status' => 'active',
                'end_date' => null,
            ])->save();

            $account->installments()->delete();
            $this->createInstallments($account, $calculation);

            CashMovement::query()
                ->where('company_id', $companyId)
                ->where('type', 'accounts_payable_disbursement')
                ->where('reference_type', $account->getMorphClass())
                ->where('reference_id', $account->id)
                ->update([
                    'amount' => (float) $account->principal_amount,
                    'movement_date' => $account->disbursement_date->toDateString(),
                    'description' => "Préstamo tomado {$account->reference}",
                ]);

            $this->auditService->record(
                action: 'account_payable_updated',
                module: 'accounts_payable',
                companyId: $companyId,
                userId: $updatedBy,
                auditable: $account,
                description: "Cuenta por pagar actualizada {$account->reference}.",
                oldValues: $oldValues,
                newValues: $account->fresh(['creditor', 'installments'])?->toArray(),
            );

            return $account->fresh(['creditor', 'installments']) ?? $account;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function registerPayment(int $companyId, int $accountId, array $data, int $createdBy): AccountPayablePayment
    {
        return DB::transaction(function () use ($companyId, $accountId, $data, $createdBy): AccountPayablePayment {
            /** @var AccountPayable $account */
            $account = AccountPayable::query()
                ->forCompany($companyId)
                ->whereKey($accountId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($account->status, ['active', 'late'], true)) {
                throw new InvalidArgumentException('Solo se pueden registrar pagos a cuentas activas o atrasadas.');
            }

            $paymentDate = CarbonImmutable::parse($data['payment_date']);
            $remaining = round((float) $data['amount'], 2);

            if ($remaining <= 0) {
                throw new InvalidArgumentException('El monto del pago debe ser mayor que cero.');
            }

            /** @var Collection<int, AccountPayableInstallment> $installments */
            $installments = $account->installments()
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->orderBy('due_date')
                ->orderBy('installment_number')
                ->lockForUpdate()
                ->get();

            $principal = 0.0;
            $interest = 0.0;
            $late = 0.0;

            foreach ($installments as $installment) {
                if ($remaining <= 0) {
                    break;
                }

                $this->refreshLateFee($account, $installment, $paymentDate);

                [$appliedLate, $remaining] = $this->applyAmount($remaining, max(0, round((float) $installment->late_fee - (float) $installment->paid_late_fee, 2)));
                [$appliedInterest, $remaining] = $this->applyAmount($remaining, max(0, round((float) $installment->interest_amount - (float) $installment->paid_interest, 2)));
                [$appliedPrincipal, $remaining] = $this->applyAmount($remaining, max(0, round((float) $installment->principal_amount - (float) $installment->paid_principal, 2)));

                $totalApplied = round($appliedLate + $appliedInterest + $appliedPrincipal, 2);
                if ($totalApplied <= 0) {
                    continue;
                }

                $installment->forceFill([
                    'paid_late_fee' => round((float) $installment->paid_late_fee + $appliedLate, 2),
                    'paid_interest' => round((float) $installment->paid_interest + $appliedInterest, 2),
                    'paid_principal' => round((float) $installment->paid_principal + $appliedPrincipal, 2),
                    'total_paid' => round((float) $installment->total_paid + $totalApplied, 2),
                ]);
                $installment->status = $this->resolveInstallmentStatus($installment);
                $installment->paid_at = $installment->status === 'paid' ? now() : null;
                $installment->save();

                $principal = round($principal + $appliedPrincipal, 2);
                $interest = round($interest + $appliedInterest, 2);
                $late = round($late + $appliedLate, 2);
            }

            $appliedAmount = round($principal + $interest + $late, 2);
            if ($appliedAmount <= 0) {
                throw new InvalidArgumentException('El pago no pudo aplicarse a ninguna cuota pendiente.');
            }

            if ($remaining > 0) {
                throw new InvalidArgumentException('El monto excede el saldo pendiente de la cuenta por pagar.');
            }

            $previousBalance = (float) $account->remaining_balance;
            $newBalance = max(0, round($previousBalance - $principal, 2));

            $account->forceFill([
                'paid_principal' => round((float) $account->paid_principal + $principal, 2),
                'paid_interest' => round((float) $account->paid_interest + $interest, 2),
                'paid_late_fee' => round((float) $account->paid_late_fee + $late, 2),
                'remaining_balance' => $newBalance,
                'status' => $newBalance <= 0 ? 'paid' : ($this->hasLateInstallments($account) ? 'late' : 'active'),
                'end_date' => $newBalance <= 0 ? $paymentDate->toDateString() : $account->end_date,
            ])->save();

            $payment = AccountPayablePayment::query()->create([
                'company_id' => $companyId,
                'account_payable_id' => $account->id,
                'creditor_id' => $account->creditor_id,
                'payment_number' => $this->paymentNumber($companyId),
                'payment_date' => $paymentDate->toDateString(),
                'amount' => $appliedAmount,
                'principal_paid' => $principal,
                'interest_paid' => $interest,
                'late_fee_paid' => $late,
                'previous_balance' => $previousBalance,
                'new_balance' => $newBalance,
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $createdBy,
            ]);

            $this->cashMovementService->create(
                companyId: $companyId,
                type: 'accounts_payable_payment',
                amount: $appliedAmount,
                direction: 'out',
                reference: $payment,
                description: "Pago cuenta por pagar {$account->reference}",
                createdBy: $createdBy,
                movementDate: $paymentDate->toDateString(),
            );

            $this->auditService->record(
                action: 'account_payable_payment_registered',
                module: 'accounts_payable',
                companyId: $companyId,
                userId: $createdBy,
                auditable: $payment,
                description: "Pago registrado a cuenta por pagar {$account->reference}.",
                newValues: $payment->toArray(),
            );

            return $payment;
        });
    }

    public function delete(int $companyId, int $accountId, int $deletedBy): void
    {
        DB::transaction(function () use ($companyId, $accountId, $deletedBy): void {
            /** @var AccountPayable $account */
            $account = AccountPayable::query()
                ->with(['installments', 'payments'])
                ->forCompany($companyId)
                ->whereKey($accountId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($account->payments->isNotEmpty()) {
                throw new InvalidArgumentException('No puedes eliminar una cuenta por pagar que ya tiene pagos registrados.');
            }

            $snapshot = $account->toArray();

            CashMovement::query()
                ->where('company_id', $companyId)
                ->where('type', 'accounts_payable_disbursement')
                ->where('reference_type', $account->getMorphClass())
                ->where('reference_id', $account->id)
                ->delete();

            $account->delete();

            $this->auditService->record(
                action: 'account_payable_deleted',
                module: 'accounts_payable',
                companyId: $companyId,
                userId: $deletedBy,
                description: "Cuenta por pagar eliminada {$snapshot['reference']}.",
                oldValues: $snapshot,
            );
        });
    }

    /**
     * @param array{installment_amount: float, total_interest: float, total_amount: float, installments: list<array{number:int, principal:float, interest:float, amount:float}>} $calculation
     */
    private function createInstallments(AccountPayable $account, array $calculation): void
    {
        $dueDate = CarbonImmutable::parse($account->first_payment_date);

        foreach ($calculation['installments'] as $installment) {
            $account->installments()->create([
                'installment_number' => $installment['number'],
                'due_date' => $dueDate->toDateString(),
                'principal_amount' => $installment['principal'],
                'interest_amount' => $installment['interest'],
                'installment_amount' => $installment['amount'],
            ]);

            $dueDate = $this->nextDueDate($dueDate, $account->payment_frequency);
        }
    }

    private function nextDueDate(CarbonImmutable $date, string $frequency): CarbonImmutable
    {
        return match ($frequency) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'biweekly' => $date->addWeeks(2),
            default => $date->addMonthNoOverflow(),
        };
    }

    private function refreshLateFee(AccountPayable $account, AccountPayableInstallment $installment, CarbonImmutable $date): void
    {
        if ($installment->status === 'paid') {
            return;
        }

        $daysLate = max(0, $installment->due_date->diffInDays($date, false) * -1);
        if ($date->lessThanOrEqualTo($installment->due_date)) {
            $daysLate = 0;
        }

        $lateFee = match ($account->late_fee_type) {
            'fixed' => $daysLate > 0 ? (float) $account->late_fee_value : 0.0,
            'daily_fixed' => $daysLate > 0 ? round($daysLate * (float) $account->late_fee_value, 2) : 0.0,
            default => 0.0,
        };

        $installment->forceFill([
            'days_late' => $daysLate,
            'late_fee' => $lateFee,
        ]);
        $installment->status = $installment->total_paid > 0
            ? 'partial'
            : ($daysLate > 0 ? 'late' : 'pending');
        $installment->save();
    }

    private function hasLateInstallments(AccountPayable $account): bool
    {
        return $account->installments()->where('status', 'late')->exists();
    }

    private function resolveInstallmentStatus(AccountPayableInstallment $installment): string
    {
        $principalDue = round((float) $installment->principal_amount - (float) $installment->paid_principal, 2);
        $interestDue = round((float) $installment->interest_amount - (float) $installment->paid_interest, 2);
        $lateDue = round((float) $installment->late_fee - (float) $installment->paid_late_fee, 2);

        if ($principalDue <= 0 && $interestDue <= 0 && $lateDue <= 0) {
            return 'paid';
        }

        return (float) $installment->total_paid > 0 ? 'partial' : ((int) $installment->days_late > 0 ? 'late' : 'pending');
    }

    /**
     * @return array{0:float,1:float}
     */
    private function applyAmount(float $available, float $due): array
    {
        $applied = min($available, $due);

        return [round($applied, 2), round($available - $applied, 2)];
    }

    private function referenceNumber(int $companyId): string
    {
        return 'CXP-'.now()->format('Ymd').'-'.$companyId.'-'.Str::upper(Str::random(4));
    }

    private function paymentNumber(int $companyId): string
    {
        return 'PAGCXP-'.now()->format('Ymd').'-'.$companyId.'-'.Str::upper(Str::random(4));
    }
}

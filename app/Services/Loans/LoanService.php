<?php

declare(strict_types=1);

namespace App\Services\Loans;

use App\Models\CompanySetting;
use App\Models\Loan;
use App\Models\LoanQuote;
use App\Services\Audit\AuditService;
use App\Services\Cash\CashMovementService;
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
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForCompany(int $companyId, array $filters = []): LengthAwarePaginator
    {
        return Loan::query()
            ->with(['client', 'collector'])
            ->forCompany($companyId)
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['client_id'] ?? null, fn (Builder $query, string $clientId) => $query->where('client_id', $clientId))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
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
            $requiresApproval = (bool) ($settings?->require_approval_for_loans ?? false);

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
            ->with(['client', 'collector', 'quote', 'installments' => fn ($query) => $query->orderBy('installment_number')])
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

            $hasPayments = $loan->payments()->where('status', 'valid')->exists();

            // Mora siempre editable: no recalcula cuotas, solo afecta cobros futuros.
            if (array_key_exists('late_fee_type', $data) && $data['late_fee_type'] !== null) {
                $loan->late_fee_type = $data['late_fee_type'];
                $loan->late_fee_value = $data['late_fee_value'] ?? 0;
            }

            if (! $hasPayments && array_key_exists('principal_amount', $data) && $data['principal_amount'] !== null) {
                $calculation = $this->calculator->calculate(
                    principal: (float) $data['principal_amount'],
                    annualRate: (float) $data['interest_rate'],
                    termQuantity: (int) $data['term_quantity'],
                    method: (string) $data['calculation_method'],
                );

                $loan->fill([
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
                    'start_date' => $data['start_date'],
                    'first_payment_date' => $data['first_payment_date'],
                ]);

                $loan->save();

                $loan->installments()->delete();
                $settings = CompanySetting::query()->where('company_id', $companyId)->first();
                $this->installmentGenerator->createForLoan($loan, $calculation, (bool) ($settings?->exclude_sundays_for_daily_loans ?? false));
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

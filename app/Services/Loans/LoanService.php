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
    ) {
    }

    /**
     * @param array<string, mixed> $filters
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
     * @param array<string, mixed> $data
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

            $loan = Loan::query()->create([
                'company_id' => $companyId,
                'client_id' => $data['client_id'],
                'collector_id' => $data['collector_id'] ?? null,
                'quote_id' => $quote?->id,
                'loan_number' => $this->nextLoanNumber($companyId),
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
                'status' => 'active',
                'guarantee_description' => $data['guarantee_description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'approved_by' => $userId,
                'created_by' => $userId,
            ]);

            $settings = CompanySetting::query()->where('company_id', $companyId)->first();
            $this->installmentGenerator->createForLoan($loan, $calculation, (bool) ($settings?->exclude_sundays_for_daily_loans ?? false));

            $this->cashMovementService->create(
                companyId: $companyId,
                type: 'loan_disbursement',
                amount: (float) $loan->principal_amount,
                direction: 'out',
                reference: $loan,
                description: "Desembolso de préstamo {$loan->loan_number}",
                createdBy: $userId,
            );

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
     * @param array<string, mixed> $data
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

    private function nextLoanNumber(int $companyId): string
    {
        $nextId = (int) Loan::query()->forCompany($companyId)->withTrashed()->count() + 1;

        return 'PRE-'.now()->format('Ymd').'-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }
}

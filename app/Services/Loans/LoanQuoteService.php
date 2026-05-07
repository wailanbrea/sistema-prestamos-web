<?php

declare(strict_types=1);

namespace App\Services\Loans;

use App\Models\LoanQuote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class LoanQuoteService
{
    public function __construct(private readonly LoanCalculatorService $calculator)
    {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function paginateForCompany(int $companyId, array $filters = []): LengthAwarePaginator
    {
        return LoanQuote::query()
            ->with('client')
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
    public function create(int $companyId, ?int $userId, array $data): LoanQuote
    {
        $calculation = $this->calculate($data);

        return LoanQuote::query()->create([
            'company_id' => $companyId,
            'client_id' => $data['client_id'] ?? null,
            'amount' => $data['amount'],
            'interest_rate' => $data['interest_rate'],
            'interest_type' => $data['interest_type'],
            'payment_frequency' => $data['payment_frequency'],
            'calculation_method' => $data['calculation_method'],
            'term_quantity' => $data['term_quantity'],
            'installment_amount' => $calculation['installment_amount'],
            'total_interest' => $calculation['total_interest'],
            'total_to_pay' => $calculation['total_amount'],
            'start_date' => $data['start_date'] ?? null,
            'first_payment_date' => $data['first_payment_date'] ?? null,
            'status' => 'pending',
            'created_by' => $userId,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{installment_amount: float, total_interest: float, total_amount: float, installments: list<array{number:int, principal:float, interest:float, amount:float}>}
     */
    public function calculate(array $data): array
    {
        return $this->calculator->calculate(
            principal: (float) $data['amount'],
            annualRate: (float) $data['interest_rate'],
            termQuantity: (int) $data['term_quantity'],
            method: (string) $data['calculation_method'],
        );
    }

    public function findForCompany(int $companyId, int $quoteId): LoanQuote
    {
        return LoanQuote::query()
            ->with(['client', 'createdBy'])
            ->forCompany($companyId)
            ->whereKey($quoteId)
            ->firstOrFail();
    }

    public function delete(LoanQuote $quote): void
    {
        if ($quote->status === 'converted') {
            throw new InvalidArgumentException('No puedes borrar una cotizacion convertida en prestamo.');
        }

        $quote->delete();
    }
}

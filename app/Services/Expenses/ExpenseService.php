<?php

declare(strict_types=1);

namespace App\Services\Expenses;

use App\Models\Expense;
use App\Services\Audit\AuditService;
use App\Services\Cash\CashMovementService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        private readonly CashMovementService $cashMovementService,
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function paginateForCompany(int $companyId, array $filters = []): LengthAwarePaginator
    {
        return Expense::query()
            ->with('category:id,name')
            ->forCompany($companyId)
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('description', 'like', "%{$search}%"))
            ->when($filters['category_id'] ?? null, fn (Builder $query, string $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['payment_method'] ?? null, fn (Builder $query, string $method) => $query->where('payment_method', $method))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('expense_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('expense_date', '<=', $date))
            ->latest('expense_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $companyId, array $data, ?int $createdBy): Expense
    {
        return DB::transaction(function () use ($companyId, $data, $createdBy): Expense {
            $data['company_id'] = $companyId;
            $data['created_by'] = $createdBy;

            $expense = Expense::query()->create($data);

            $this->cashMovementService->create(
                companyId: $companyId,
                type: 'expense',
                amount: (float) $expense->amount,
                direction: 'out',
                reference: $expense,
                description: "Gasto registrado: {$expense->description}",
                createdBy: $createdBy,
            );

            $this->auditService->record(
                action: 'expense_created',
                module: 'expenses',
                companyId: $companyId,
                userId: $createdBy,
                auditable: $expense,
                description: 'Gasto registrado.',
                newValues: $expense->toArray(),
            );

            return $expense->fresh(['category']) ?? $expense;
        });
    }

    public function findForCompany(int $companyId, int $expenseId): Expense
    {
        return Expense::query()
            ->with('category:id,name')
            ->forCompany($companyId)
            ->whereKey($expenseId)
            ->firstOrFail();
    }
}

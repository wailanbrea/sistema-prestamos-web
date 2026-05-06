<?php

declare(strict_types=1);

namespace App\Services\Expenses;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ExpenseCategoryService
{
    /**
     * @return Collection<int, ExpenseCategory>
     */
    public function listForCompany(int $companyId): Collection
    {
        return ExpenseCategory::query()
            ->forCompany($companyId)
            ->withCount('expenses')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $companyId, array $data): ExpenseCategory
    {
        $data['company_id'] = $companyId;

        return ExpenseCategory::query()->create($data);
    }

    public function findForCompany(int $companyId, int $categoryId): ExpenseCategory
    {
        return ExpenseCategory::query()
            ->forCompany($companyId)
            ->whereKey($categoryId)
            ->firstOrFail();
    }

    public function delete(ExpenseCategory $category): void
    {
        if ($category->expenses()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'No se puede eliminar una categoría con gastos vinculados.',
            ]);
        }

        $category->delete();
    }
}

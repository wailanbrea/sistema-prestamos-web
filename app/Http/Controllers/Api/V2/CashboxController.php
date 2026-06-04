<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\Cash\CashMovementReportService;
use App\Services\Expenses\ExpenseCategoryService;
use App\Services\Expenses\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Caja/Contabilidad para la app móvil: registrar y consultar gastos, y ver los
 * movimientos de caja + su balance. Reutiliza los servicios del módulo web
 * (ExpenseService registra el gasto y su movimiento de caja en una transacción).
 */
class CashboxController extends Controller
{
    use BuildsApiPayloads;

    public function __construct(
        private readonly ExpenseService $expenseService,
        private readonly ExpenseCategoryService $expenseCategoryService,
        private readonly CashMovementReportService $cashReportService,
    ) {
    }

    public function expenses(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $expenses = $this->expenseService->paginateForCompany($companyId, $request->only(['search', 'category_id', 'payment_method', 'date_from', 'date_to']));

        return response()->json([
            'data' => $expenses->through(fn (Expense $expense): array => $this->expensePayload($expense))->items(),
            'meta' => $this->paginationMeta($expenses),
        ]);
    }

    public function storeExpense(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', Rule::exists('expense_categories', 'id')->where('company_id', $companyId)],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'card', 'check', 'other'])],
        ]);

        $expense = $this->expenseService->create($companyId, $validated, (int) $request->user()->id);

        return response()->json(['data' => $this->expensePayload($expense)], 201);
    }

    public function expenseCategories(Request $request): JsonResponse
    {
        $categories = $this->expenseCategoryService->listForCompany((int) $request->user()->company_id);

        return response()->json([
            'data' => $categories->map(fn (ExpenseCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values(),
        ]);
    }

    public function movements(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $movements = $this->cashReportService->paginateForCompany($companyId, $request->only(['type', 'direction', 'date_from', 'date_to']));

        return response()->json([
            'data' => $movements->through(fn (CashMovement $movement): array => $this->movementPayload($movement))->items(),
            'meta' => $this->paginationMeta($movements),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        return response()->json([
            'data' => $this->cashReportService->totalsForCompany($companyId, $request->only(['type', 'direction', 'date_from', 'date_to'])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function expensePayload(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'date' => $expense->expense_date?->toDateString(),
            'category' => $expense->category?->name,
            'category_id' => $expense->category_id,
            'description' => $expense->description,
            'amount' => (float) $expense->amount,
            'payment_method' => $expense->payment_method,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function movementPayload(CashMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'type' => $movement->type,
            'amount' => (float) $movement->amount,
            'direction' => $movement->direction,
            'description' => $movement->description,
            'date' => $movement->movement_date?->toDateString(),
        ];
    }
}

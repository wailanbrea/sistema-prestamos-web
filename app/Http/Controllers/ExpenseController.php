<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Expenses\StoreExpenseRequest;
use App\Services\Expenses\ExpenseCategoryService;
use App\Services\Expenses\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenseService,
        private readonly ExpenseCategoryService $categoryService,
    ) {
    }

    public function index(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;
        $filters = $request->only(['search', 'category_id', 'payment_method', 'date_from', 'date_to']);

        return view('expenses.index', [
            'expenses' => $this->expenseService->paginateForCompany($companyId, $filters),
            'categories' => $this->categoryService->listForCompany($companyId),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        return view('expenses.create', [
            'categories' => $this->categoryService->listForCompany((int) $request->user()->company_id),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = $this->expenseService->create(
            (int) $request->user()->company_id,
            $request->validated(),
            $request->user()->id,
        );

        return redirect()
            ->route('expenses.show', $expense)
            ->with('status', 'Gasto registrado correctamente.');
    }

    public function show(Request $request, int $expense): View
    {
        return view('expenses.show', [
            'expense' => $this->expenseService->findForCompany((int) $request->user()->company_id, $expense),
        ]);
    }
}

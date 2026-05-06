<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Expenses\StoreExpenseCategoryRequest;
use App\Services\Expenses\ExpenseCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExpenseCategoryController extends Controller
{
    public function __construct(private readonly ExpenseCategoryService $categoryService)
    {
    }

    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        $this->categoryService->create((int) $request->user()->company_id, $request->validated());

        return redirect()
            ->route('expenses.index')
            ->with('status', 'Categoría creada correctamente.');
    }

    public function destroy(Request $request, int $category): RedirectResponse
    {
        abort_unless($request->user()?->can('expenses.manage'), 403);

        try {
            $this->categoryService->delete($this->categoryService->findForCompany((int) $request->user()->company_id, $category));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('expenses.index')
            ->with('status', 'Categoría eliminada correctamente.');
    }
}

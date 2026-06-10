<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AccountsPayable\StoreCreditorRequest;
use App\Http\Requests\AccountsPayable\UpdateCreditorRequest;
use App\Services\AccountsPayable\CreditorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CreditorController extends Controller
{
    public function __construct(
        private readonly CreditorService $creditorService,
    ) {
    }

    public function index(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;
        $filters = $request->only(['search', 'status']);

        return view('creditors.index', [
            'creditors' => $this->creditorService->paginateForCompany($companyId, $filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('creditors.create');
    }

    public function store(StoreCreditorRequest $request): RedirectResponse
    {
        $creditor = $this->creditorService->create((int) $request->user()->company_id, $request->validated());

        return redirect()
            ->route('creditors.index')
            ->with('status', "Acreedor {$creditor->name} registrado correctamente.");
    }

    public function edit(Request $request, int $creditor): View
    {
        return view('creditors.edit', [
            'creditor' => $this->creditorService->findForCompany((int) $request->user()->company_id, $creditor),
        ]);
    }

    public function update(UpdateCreditorRequest $request, int $creditor): RedirectResponse
    {
        $model = $this->creditorService->findForCompany((int) $request->user()->company_id, $creditor);
        $this->creditorService->update($model, $request->validated());

        return redirect()
            ->route('creditors.index')
            ->with('status', 'Acreedor actualizado correctamente.');
    }

    public function destroy(Request $request, int $creditor): RedirectResponse
    {
        $model = $this->creditorService->findForCompany((int) $request->user()->company_id, $creditor);

        try {
            $this->creditorService->delete($model);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['creditor' => $exception->getMessage()]);
        }

        return redirect()
            ->route('creditors.index')
            ->with('status', 'Acreedor eliminado correctamente.');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Collectors\StoreCollectorRequest;
use App\Http\Requests\Collectors\UpdateCollectorRequest;
use App\Services\Collectors\CollectorCommissionService;
use App\Models\User;
use App\Services\Collectors\CollectorService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CollectorController extends Controller
{
    public function __construct(
        private readonly CollectorService $collectorService,
        private readonly CollectorCommissionService $commissionService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'commission_type']);

        return view('collectors.index', [
            'collectors' => $this->collectorService->paginateForCompany((int) $request->user()->company_id, $filters),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        return view('collectors.create', [
            'users' => $this->companyUsers((int) $request->user()->company_id),
        ]);
    }

    public function store(StoreCollectorRequest $request): RedirectResponse
    {
        $collector = $this->collectorService->create((int) $request->user()->company_id, $request->validated());

        return redirect()
            ->route('collectors.show', $collector)
            ->with('status', 'Cobrador creado correctamente.');
    }

    public function show(Request $request, int $collector): View
    {
        $companyId = (int) $request->user()->company_id;
        $collectorModel = $this->collectorService->findForCompany($companyId, $collector);

        return view('collectors.show', [
            'collector' => $collectorModel,
            'commissionSummary' => $this->commissionService->summaryForCollector($companyId, (int) $collectorModel->id),
        ]);
    }

    public function edit(Request $request, int $collector): View
    {
        return view('collectors.edit', [
            'collector' => $this->collectorService->findForCompany((int) $request->user()->company_id, $collector),
            'users' => $this->companyUsers((int) $request->user()->company_id),
        ]);
    }

    public function update(UpdateCollectorRequest $request, int $collector): RedirectResponse
    {
        $model = $this->collectorService->findForCompany((int) $request->user()->company_id, $collector);
        $this->collectorService->update($model, $request->validated());

        return redirect()
            ->route('collectors.show', $model)
            ->with('status', 'Cobrador actualizado correctamente.');
    }

    public function destroy(Request $request, int $collector): RedirectResponse
    {
        $model = $this->collectorService->findForCompany((int) $request->user()->company_id, $collector);
        $this->collectorService->delete($model);

        return redirect()
            ->route('collectors.index')
            ->with('status', 'Cobrador eliminado correctamente.');
    }

    public function payCommission(Request $request, int $collector, int $commission): RedirectResponse
    {
        try {
            $this->commissionService->pay(
                companyId: (int) $request->user()->company_id,
                collectorId: $collector,
                commissionId: $commission,
                paidBy: (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['commission' => $exception->getMessage()]);
        }

        return redirect()
            ->route('collectors.show', $collector)
            ->with('status', 'Comision pagada correctamente.');
    }

    /**
     * @return Collection<int, User>
     */
    private function companyUsers(int $companyId): Collection
    {
        return User::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}

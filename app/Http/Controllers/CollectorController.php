<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Collectors\StoreCollectorRequest;
use App\Http\Requests\Collectors\UpdateCollectorRequest;
use App\Models\Collector as CollectorModel;
use App\Models\Loan;
use App\Models\User;
use App\Services\Collectors\CollectorCommissionService;
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
            'assignableLoans' => $this->assignableLoans((int) $request->user()->company_id),
        ]);
    }

    public function store(StoreCollectorRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $collector = $this->collectorService->create((int) $request->user()->company_id, $validated);

        $redirect = redirect()
            ->route('collectors.show', $collector)
            ->with('status', 'Cobrador creado correctamente.');

        if (($validated['access_mode'] ?? null) === 'new') {
            $redirect->with('collector_credentials', [
                'user_name' => $validated['user_name'] ?: $validated['name'],
                'email' => $validated['user_email'],
                'password' => $validated['user_password'],
            ]);
        }

        return $redirect;
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
        $collectorModel = $this->collectorService->findForCompany((int) $request->user()->company_id, $collector);

        return view('collectors.edit', [
            'collector' => $collectorModel,
            'users' => $this->companyUsers((int) $request->user()->company_id, (int) $collectorModel->user_id),
            'assignableLoans' => $this->assignableLoans((int) $request->user()->company_id, (int) $collectorModel->id),
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
    private function companyUsers(int $companyId, ?int $includeUserId = null): Collection
    {
        $linkedUserIds = CollectorModel::query()
            ->forCompany($companyId)
            ->pluck('user_id')
            ->filter()
            ->all();

        return User::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->when($linkedUserIds !== [], function ($query) use ($linkedUserIds, $includeUserId): void {
                $query->where(function ($nested) use ($linkedUserIds, $includeUserId): void {
                    $nested->whereNotIn('id', $linkedUserIds);
                    if ($includeUserId) {
                        $nested->orWhere('id', $includeUserId);
                    }
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /**
     * @return Collection<int, Loan>
     */
    private function assignableLoans(int $companyId, ?int $collectorId = null): Collection
    {
        return Loan::query()
            ->with(['client:id,full_name', 'collector:id,name'])
            ->forCompany($companyId)
            ->whereIn('status', ['active', 'late'])
            ->when($collectorId, function ($query) use ($collectorId): void {
                $query->orderByRaw('case when collector_id = ? then 0 when collector_id is null then 1 else 2 end', [$collectorId]);
            }, function ($query): void {
                $query->orderByRaw('case when collector_id is null then 0 else 1 end');
            })
            ->orderBy('loan_number')
            ->get(['id', 'company_id', 'client_id', 'collector_id', 'loan_number', 'currency', 'remaining_balance', 'status']);
    }
}

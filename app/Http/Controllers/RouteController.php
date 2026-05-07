<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Routes\StoreRouteRequest;
use App\Http\Requests\Routes\UpdateRouteRequest;
use App\Models\Client;
use App\Models\Collector;
use App\Services\Routes\RouteService;
use App\Services\Routes\RouteMapService;
use App\Services\Routes\ZoneService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouteController extends Controller
{
    public function __construct(
        private readonly RouteService $routeService,
        private readonly ZoneService $zoneService,
        private readonly RouteMapService $routeMapService,
    ) {
    }

    public function index(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;
        $filters = $request->only(['search', 'status', 'zone_id']);

        return view('routes.index', [
            'routes' => $this->routeService->paginateForCompany($companyId, $filters),
            'zones' => $this->zoneService->listForCompany($companyId),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        return view('routes.create', $this->formData((int) $request->user()->company_id));
    }

    public function map(Request $request): View
    {
        $filters = $request->only(['collector_id', 'route_id']);

        return view('routes.map', [
            ...$this->routeMapService->dataForCompany((int) $request->user()->company_id, $filters),
            'filters' => $filters,
            'googleMapsApiKey' => (string) config('services.google_maps.api_key'),
        ]);
    }

    public function store(StoreRouteRequest $request): RedirectResponse
    {
        $route = $this->routeService->create((int) $request->user()->company_id, $request->validated());

        return redirect()
            ->route('routes.show', $route)
            ->with('status', 'Ruta creada correctamente.');
    }

    public function show(Request $request, int $route): View
    {
        return view('routes.show', [
            'routeModel' => $this->routeService->findForCompany((int) $request->user()->company_id, $route),
        ]);
    }

    public function edit(Request $request, int $route): View
    {
        $companyId = (int) $request->user()->company_id;

        return view('routes.edit', [
            ...$this->formData($companyId),
            'routeModel' => $this->routeService->findForCompany($companyId, $route),
        ]);
    }

    public function update(UpdateRouteRequest $request, int $route): RedirectResponse
    {
        $model = $this->routeService->findForCompany((int) $request->user()->company_id, $route);
        $this->routeService->update($model, $request->validated());

        return redirect()
            ->route('routes.show', $model)
            ->with('status', 'Ruta actualizada correctamente.');
    }

    public function destroy(Request $request, int $route): RedirectResponse
    {
        abort_unless($request->user()?->can('routes.manage'), 403);

        $this->routeService->delete($this->routeService->findForCompany((int) $request->user()->company_id, $route));

        return redirect()
            ->route('routes.index')
            ->with('status', 'Ruta eliminada correctamente.');
    }

    /**
     * @return array{zones:Collection<int,\App\Models\Zone>,collectors:Collection<int,Collector>,clients:Collection<int,Client>}
     */
    private function formData(int $companyId): array
    {
        return [
            'zones' => $this->zoneService->listForCompany($companyId),
            'collectors' => Collector::query()->forCompany($companyId)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'clients' => Client::query()
                ->forCompany($companyId)
                ->where('status', '!=', 'blocked')
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'phone', 'address', 'latitude', 'longitude']),
        ];
    }
}

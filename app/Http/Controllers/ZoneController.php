<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Routes\StoreZoneRequest;
use App\Services\Routes\ZoneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ZoneController extends Controller
{
    public function __construct(private readonly ZoneService $zoneService)
    {
    }

    public function store(StoreZoneRequest $request): RedirectResponse
    {
        $this->zoneService->create((int) $request->user()->company_id, $request->validated());

        return redirect()
            ->route('routes.index')
            ->with('status', 'Zona creada correctamente.');
    }

    public function destroy(Request $request, int $zone): RedirectResponse
    {
        abort_unless($request->user()?->can('routes.manage'), 403);

        try {
            $this->zoneService->delete($this->zoneService->findForCompany((int) $request->user()->company_id, $zone));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('routes.index')
            ->with('status', 'Zona eliminada correctamente.');
    }
}

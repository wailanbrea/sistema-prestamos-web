<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Cash\StoreManualCashMovementRequest;
use App\Services\Cash\ManualCashMovementService;
use App\Services\Cash\CashMovementReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashMovementController extends Controller
{
    public function __construct(
        private readonly CashMovementReportService $cashReportService,
        private readonly ManualCashMovementService $manualCashMovementService,
    ) {
    }

    public function index(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;
        $filters = $request->only(['type', 'direction', 'date_from', 'date_to']);

        return view('cash-movements.index', [
            'movements' => $this->cashReportService->paginateForCompany($companyId, $filters),
            'totals' => $this->cashReportService->totalsForCompany($companyId, $filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('cash-movements.create');
    }

    public function store(StoreManualCashMovementRequest $request): RedirectResponse
    {
        $this->manualCashMovementService->create(
            (int) $request->user()->company_id,
            $request->validated(),
            (int) $request->user()->id,
        );

        return redirect()
            ->route('cash-movements.index')
            ->with('status', 'Movimiento de caja registrado correctamente.');
    }
}

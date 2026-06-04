<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportService;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reportes para la app móvil (roles con `reports.view`). Reutiliza el
 * ReportService del módulo web: resumen financiero/inversión y consolidado por
 * cobrador. ReportScope aísla por empresa y rol, así que es seguro vía API.
 */
class AdminReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function summary(Request $request): JsonResponse
    {
        $filters = ReportFilters::fromRequest($request);
        $scope = new ReportScope($request->user(), $filters);

        return response()->json([
            'data' => $this->reports->getFinancialInvestmentSummary($scope, $filters),
        ]);
    }

    public function collectors(Request $request): JsonResponse
    {
        $filters = ReportFilters::fromRequest($request);
        $scope = new ReportScope($request->user(), $filters);

        return response()->json([
            'data' => $this->reports->getConsolidatedWeeklySummary($scope, $filters),
        ]);
    }
}

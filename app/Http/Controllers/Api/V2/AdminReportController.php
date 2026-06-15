<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReportController as WebReportController;
use App\Services\Reports\ReportService;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

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

    /**
     * Catálogo de los reportes de la web, cada uno con un enlace firmado y
     * temporal a su PDF. La app abre el enlace para verlo/imprimirlo.
     */
    public function catalog(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $items = collect(WebReportController::reportsCatalog())
            ->map(fn (array $config, string $type): array => [
                'type' => $type,
                'title' => $config['title'],
                'description' => $config['description'],
                'pdf_url' => URL::temporarySignedRoute(
                    'reports.public-pdf',
                    now()->addHours(6),
                    ['type' => $type, 'u' => $userId],
                ),
            ])
            ->values();

        return response()->json(['data' => $items]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReportController;
use App\Models\User;
use App\Services\Reports\Export\ReportPdfExporter;
use App\Services\Reports\ReportService;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportScope;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Genera el PDF de un reporte para la app móvil mediante un enlace firmado
 * (sin sesión web). El usuario va en el parámetro `u` del enlace firmado, así
 * que no se puede manipular sin invalidar la firma.
 */
class PublicReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly ReportPdfExporter $pdfExporter,
    ) {
    }

    public function pdf(Request $request, string $type): Response
    {
        $catalog = ReportController::reportsCatalog();
        abort_unless(isset($catalog[$type]), 404);

        $user = User::query()->findOrFail((int) $request->query('u'));

        $config = $catalog[$type];
        $filters = ReportFilters::fromRequest($request);
        $scope = new ReportScope($user, $filters);
        $data = $this->reports->{$config['method']}($scope, $filters);

        return $this->pdfExporter->download($type, $config['title'], $data, $filters, $user);
    }
}

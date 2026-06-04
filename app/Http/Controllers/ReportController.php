<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Collector;
use App\Models\Route;
use App\Models\Zone;
use App\Services\Reports\Export\ReportExcelExporter;
use App\Services\Reports\Export\ReportPdfExporter;
use App\Services\Reports\Export\ReportPresenter;
use App\Services\Reports\FinancialReportService;
use App\Services\Reports\ReportService;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Catálogo de reportes: tipo (slug usado en URLs) => metadatos. Una sola
     * fuente de verdad para pantallas, exportación PDF y exportación Excel.
     *
     * @var array<string, array{method:string,route:string,title:string,icon:string,description:string}>
     */
    private const REPORTS = [
        'resumen-semanal' => [
            'method' => 'getWeeklySummary', 'route' => 'reports.weekly',
            'title' => 'Resumen semanal', 'icon' => 'fa-calendar-week',
            'description' => 'Capital, interés, entregas y gastos por día.',
        ],
        'semanal-consolidado' => [
            'method' => 'getConsolidatedWeeklySummary', 'route' => 'reports.weekly-consolidated',
            'title' => 'Semanal consolidado', 'icon' => 'fa-layer-group',
            'description' => 'Consolidado por cobrador/ruta con cuentas activas y atrasadas.',
        ],
        'resumen-anual' => [
            'method' => 'getAnnualSummary', 'route' => 'reports.annual',
            'title' => 'Resumen anual', 'icon' => 'fa-calendar-days',
            'description' => 'Capital, interés, entregas y gastos por mes.',
        ],
        'prestamos-entregados' => [
            'method' => 'getDisbursedLoansReport', 'route' => 'reports.disbursed',
            'title' => 'Préstamos entregados', 'icon' => 'fa-hand-holding-dollar',
            'description' => 'Listado de préstamos desembolsados en el período.',
        ],
        'elegibles-renovar' => [
            'method' => 'getRenewalEligibleClients', 'route' => 'reports.renewal',
            'title' => 'Elegibles para renovar', 'icon' => 'fa-rotate',
            'description' => 'Clientes con buen avance de pago aptos para renovar.',
        ],
        'activos-atraso' => [
            'method' => 'getActiveOverdueClients', 'route' => 'reports.active-overdue',
            'title' => 'Activos con atraso', 'icon' => 'fa-triangle-exclamation',
            'description' => 'Cartera activa con cuotas vencidas por nivel de atraso.',
        ],
        'inactivos-atraso' => [
            'method' => 'getInactiveOverdueClients', 'route' => 'reports.inactive-overdue',
            'title' => 'Inactivos con atraso', 'icon' => 'fa-user-clock',
            'description' => 'Clientes inactivos que aún tienen deuda pendiente.',
        ],
        'gastos' => [
            'method' => 'getExpensesReport', 'route' => 'reports.expenses',
            'title' => 'Gastos', 'icon' => 'fa-receipt',
            'description' => 'Gastos por período, categoría y usuario.',
        ],
        'ganancias' => [
            'method' => 'getProfitReport', 'route' => 'reports.profit',
            'title' => 'Ganancias', 'icon' => 'fa-chart-line',
            'description' => 'Ganancia bruta y neta con gastos y comisiones.',
        ],
        'resumen-financiero' => [
            'method' => 'getFinancialInvestmentSummary', 'route' => 'reports.financial-summary',
            'title' => 'Resumen financiero', 'icon' => 'fa-sack-dollar',
            'description' => 'Inversión, capital en calle, ROI y rentabilidad.',
        ],
    ];

    public function __construct(
        private readonly ReportService $reports,
        private readonly FinancialReportService $financialReport,
        private readonly ReportPdfExporter $pdfExporter,
        private readonly ReportExcelExporter $excelExporter,
        private readonly ReportPresenter $presenter,
    ) {
    }

    /** Pantalla principal de Informes con tarjetas. */
    public function index(): View
    {
        return view('reports.index', ['reports' => self::REPORTS]);
    }

    public function weeklySummary(Request $request): View
    {
        return $this->render($request, 'resumen-semanal');
    }

    public function weeklyConsolidated(Request $request): View
    {
        return $this->render($request, 'semanal-consolidado');
    }

    public function annualSummary(Request $request): View
    {
        return $this->render($request, 'resumen-anual');
    }

    public function disbursedLoans(Request $request): View
    {
        return $this->render($request, 'prestamos-entregados');
    }

    public function renewalEligible(Request $request): View
    {
        return $this->render($request, 'elegibles-renovar');
    }

    public function activeOverdue(Request $request): View
    {
        return $this->render($request, 'activos-atraso');
    }

    public function inactiveOverdue(Request $request): View
    {
        return $this->render($request, 'inactivos-atraso');
    }

    public function expenses(Request $request): View
    {
        return $this->render($request, 'gastos');
    }

    public function profit(Request $request): View
    {
        return $this->render($request, 'ganancias');
    }

    public function financialSummary(Request $request): View
    {
        return $this->render($request, 'resumen-financiero');
    }

    /** Exportación PDF de cualquier reporte del catálogo. */
    public function exportPdf(Request $request, string $type): Response
    {
        $config = $this->configFor($type);
        [$data, $filters] = $this->build($request, $type);

        return $this->pdfExporter->download($type, $config['title'], $data, $filters, $request->user());
    }

    /** Exportación Excel (.xlsx) de cualquier reporte del catálogo. */
    public function exportExcel(Request $request, string $type): StreamedResponse
    {
        $config = $this->configFor($type);
        [$data] = $this->build($request, $type);

        return $this->excelExporter->download($type, $config['title'], $data);
    }

    /** Resuelve filtros, scope y datos, y renderiza la pantalla genérica de reporte. */
    private function render(Request $request, string $type): View
    {
        $config = $this->configFor($type);
        [$data, $filters, $scope] = $this->build($request, $type);

        return view('reports.report', [
            'data' => $data,
            'table' => $this->presenter->present($type, $data),
            'filters' => $filters,
            'options' => $this->filterOptions($scope),
            'type' => $type,
            'config' => $config,
            'title' => $config['title'],
        ]);
    }

    /**
     * Construye los datos del reporte. Centraliza la creación de filtros + scope.
     *
     * @return array{0: array<string, mixed>, 1: ReportFilters, 2: ReportScope}
     */
    private function build(Request $request, string $type): array
    {
        $config = $this->configFor($type);
        $filters = ReportFilters::fromRequest($request);
        $scope = new ReportScope($request->user(), $filters);
        $data = $this->reports->{$config['method']}($scope, $filters);

        return [$data, $filters, $scope];
    }

    /**
     * @return array{method:string,view:string,title:string,icon:string,description:string}
     */
    private function configFor(string $type): array
    {
        abort_unless(isset(self::REPORTS[$type]), 404);

        return self::REPORTS[$type];
    }

    /**
     * Opciones para los selects de filtros, aisladas por empresa (y por cobrador
     * si el usuario está restringido a su propia cartera).
     *
     * @return array<string, \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>>
     */
    private function filterOptions(ReportScope $scope): array
    {
        $companyId = $scope->companyId();
        $collectors = Collector::query()->where('company_id', $companyId)->orderBy('name');

        if ($restricted = $scope->effectiveCollectorId()) {
            $collectors->where('id', $restricted);
        }

        return [
            'zones' => Zone::query()->where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'routes' => Route::query()->where('company_id', $companyId)->orderBy('name')->get(['id', 'name', 'zone_id']),
            'collectors' => $collectors->get(['id', 'name']),
        ];
    }

    // -----------------------------------------------------------------
    // Reporte financiero legacy (se conserva intacto).
    // -----------------------------------------------------------------

    /** Dashboard financiero clásico (atrasos, cartera, rendimiento por cobrador). */
    public function financialDashboard(Request $request): View
    {
        $filters = $this->financialFilters($request);

        return view('reports.financial', [
            'report' => $this->financialReport->build((int) $request->user()->company_id, $filters),
            'filters' => $filters,
        ]);
    }

    public function pdf(Request $request): Response
    {
        $filters = $this->financialFilters($request);
        $report = $this->financialReport->build((int) $request->user()->company_id, $filters);

        return Pdf::loadView('reports.pdf.financial', [
            'report' => $report,
            'company' => $request->user()->company,
        ])->download('reporte-financiero-'.$report['period']['date_from'].'-'.$report['period']['date_to'].'.pdf');
    }

    public function csv(Request $request): StreamedResponse
    {
        $filters = $this->financialFilters($request);
        $report = $this->financialReport->build((int) $request->user()->company_id, $filters);
        $filename = 'reporte-financiero-'.$report['period']['date_from'].'-'.$report['period']['date_to'].'.csv';

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Métrica', 'Valor']);
            foreach ($report['summary'] as $key => $value) {
                fputcsv($handle, [$key, $value]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Cliente', 'Teléfono', 'Préstamos', 'Balance pendiente', 'Ganancia cobrada']);
            foreach ($report['by_client'] as $row) {
                fputcsv($handle, [$row->full_name, $row->phone, $row->loans_count, $row->remaining_balance, $row->profit_collected]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Cobrador', 'Cobros', 'Total cobrado', 'Capital', 'Interés', 'Mora']);
            foreach ($report['by_collector'] as $row) {
                fputcsv($handle, [$row->name, $row->payments_count, $row->total_collected, $row->principal_collected, $row->interest_collected, $row->late_fee_collected]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{date_from:string|null,date_to:string|null}
     */
    private function financialFilters(Request $request): array
    {
        return [
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
        ];
    }
}

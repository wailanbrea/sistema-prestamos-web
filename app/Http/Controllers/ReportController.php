<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Reports\FinancialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly FinancialReportService $reportService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('reports.index', [
            'report' => $this->reportService->build((int) $request->user()->company_id, $filters),
            'filters' => $filters,
        ]);
    }

    public function pdf(Request $request): Response
    {
        $filters = $this->filters($request);
        $report = $this->reportService->build((int) $request->user()->company_id, $filters);

        return Pdf::loadView('reports.pdf.financial', [
            'report' => $report,
            'company' => $request->user()->company,
        ])->download('reporte-financiero-'.$report['period']['date_from'].'-'.$report['period']['date_to'].'.pdf');
    }

    public function csv(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $report = $this->reportService->build((int) $request->user()->company_id, $filters);
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
    private function filters(Request $request): array
    {
        return [
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Reports\Export;

use App\Models\User;
use App\Support\Reports\ReportFilters;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Genera el PDF de cualquier reporte usando una única plantilla genérica
 * (reports.pdf.report) alimentada por el ReportPresenter. Incluye logo/empresa,
 * título, filtros aplicados, fecha de generación, tabla, totales y pie.
 */
class ReportPdfExporter
{
    public function __construct(private readonly ReportPresenter $presenter)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function download(string $type, string $title, array $data, ReportFilters $filters, User $user): Response
    {
        $table = $this->presenter->present($type, $data);

        $pdf = Pdf::loadView('reports.pdf.report', [
            'title' => $title,
            'table' => $table,
            'period' => $data['period'] ?? null,
            'filters' => $filters,
            'company' => $user->company,
            'generatedAt' => now(),
            'currency' => currency(),
        ])->setPaper('a4', 'landscape');

        $filename = Str::slug($title).'-'.now()->format('Ymd-His').'.pdf';

        return $pdf->download($filename);
    }
}

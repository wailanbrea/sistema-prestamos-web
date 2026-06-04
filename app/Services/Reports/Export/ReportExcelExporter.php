<?php

declare(strict_types=1);

namespace App\Services\Reports\Export;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Genera un .xlsx real para cualquier reporte usando PhpSpreadsheet, a partir de
 * la estructura normalizada del ReportPresenter: encabezados en negrita, formato
 * de moneda en columnas monetarias, fila de totales y nombre de hoja por reporte.
 */
class ReportExcelExporter
{
    public function __construct(private readonly ReportPresenter $presenter)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function download(string $type, string $title, array $data): StreamedResponse
    {
        $table = $this->presenter->present($type, $data);
        $moneyFormat = '"'.currency().'" #,##0.00';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(Str::limit(Str::ascii($title), 28, ''));

        $row = 1;
        $sheet->setCellValue("A{$row}", $title);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $row++;

        if (! empty($data['period']['label'])) {
            $sheet->setCellValue("A{$row}", 'Período: '.$data['period']['label']);
            $row++;
        }
        $sheet->setCellValue("A{$row}", 'Generado: '.now()->format('d/m/Y H:i'));
        $row += 2;

        // Bloque resumen (key/value), útil para reportes sin tabla (ganancias, financiero).
        if (! empty($table['summary'])) {
            foreach ($table['summary'] as $item) {
                $sheet->setCellValue("A{$row}", $item['label']);
                $sheet->setCellValue("B{$row}", $item['value']);
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                if ($item['money']) {
                    $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode($moneyFormat);
                }
                $row++;
            }
            $row++;
        }

        // Tabla principal.
        if (! empty($table['columns'])) {
            $columnCount = count($table['columns']);

            foreach (array_values($table['columns']) as $i => $column) {
                $sheet->setCellValue($this->cell($i + 1, $row), $column['label']);
            }
            $this->styleHeader($sheet, $row, $columnCount);
            $row++;

            foreach ($table['rows'] as $dataRow) {
                foreach (array_values($table['columns']) as $i => $column) {
                    $coord = $this->cell($i + 1, $row);
                    $sheet->setCellValue($coord, $dataRow[$column['key']] ?? null);
                    if ($column['money']) {
                        $sheet->getStyle($coord)->getNumberFormat()->setFormatCode($moneyFormat);
                    }
                }
                $row++;
            }

            // Fila de totales.
            if (! empty($table['totals'])) {
                foreach (array_values($table['columns']) as $i => $column) {
                    $coord = $this->cell($i + 1, $row);
                    if ($i === 0) {
                        $sheet->setCellValue($coord, 'TOTAL');
                    } elseif (array_key_exists($column['key'], $table['totals'])) {
                        $sheet->setCellValue($coord, $table['totals'][$column['key']]);
                        if ($column['money']) {
                            $sheet->getStyle($coord)->getNumberFormat()->setFormatCode($moneyFormat);
                        }
                    }
                }
                $this->styleHeader($sheet, $row, $columnCount);
            }

            for ($i = 1; $i <= $columnCount; $i++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
            }
        }

        $filename = Str::slug($title).'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function cell(int $columnIndex, int $row): string
    {
        return Coordinate::stringFromColumnIndex($columnIndex).$row;
    }

    private function styleHeader(Worksheet $sheet, int $row, int $columnCount): void
    {
        $range = 'A'.$row.':'.$this->cell($columnCount, $row);
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9ECEF');
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }
}

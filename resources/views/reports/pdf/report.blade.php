<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        body { margin: 0; font-size: 10px; color: #1f2937; }
        .header { border-bottom: 2px solid #5e72e4; padding-bottom: 8px; margin-bottom: 12px; }
        .company { font-size: 16px; font-weight: 700; }
        .muted { color: #6b7280; }
        .title { font-size: 13px; font-weight: 700; margin: 10px 0 2px; }
        .summary { width: 100%; border-collapse: collapse; margin: 8px 0 12px; }
        .summary td { border: 0; padding: 3px 6px; vertical-align: top; }
        .summary .label { color: #6b7280; }
        .report-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 8px; }
        .report-table th,
        .report-table td {
            border: 1px solid #e5e7eb;
            padding: 4px 5px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .report-table thead th {
            background: #eef0fb;
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
        }
        .report-table td.num,
        .report-table th.num { text-align: right; }
        .report-table tfoot td { background: #f3f4f6; font-weight: 700; }
        .col-client { width: 15%; }
        .col-phone { width: 9%; }
        .col-loan-number { width: 12%; }
        .col-overdue-installments { width: 6%; }
        .col-days-late { width: 5%; }
        .col-bucket { width: 7%; }
        .col-principal,
        .col-interest,
        .col-late-fee,
        .col-total { width: 9%; }
        .col-collector { width: 10%; }
        .col-last-payment { width: 9%; }
        .col-days-since-payment { width: 8%; }
        .footer {
            margin-top: 18px;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
@php
    $money = fn ($v) => $currency.' '.number_format((float) $v, 2);
    $columnClass = static function (string $key, bool $money): string {
        $base = 'col-'.str_replace('_', '-', $key);
        return trim($base.' '.($money ? 'num' : ''));
    };
@endphp

<div class="header">
    <div class="company">{{ $company?->name ?? config('app.name') }}</div>
    @if ($company?->address)
        <div class="muted">{{ $company->address }}@if($company->phone) - {{ $company->phone }}@endif</div>
    @endif
    <div class="title">{{ $title }}</div>
    <div class="muted">
        Periodo: {{ $period['label'] ?? '-' }} -
        Generado: {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</div>

@if (! empty($table['summary']))
    <table class="summary">
        @foreach (array_chunk($table['summary'], 3) as $chunk)
            <tr>
                @foreach ($chunk as $item)
                    <td style="width:33%">
                        <span class="label">{{ $item['label'] }}:</span>
                        <strong>{{ $item['money'] ? $money($item['value']) : $item['value'] }}</strong>
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
@endif

@if (! empty($table['columns']))
    <table class="report-table">
        <thead>
            <tr>
                @foreach ($table['columns'] as $column)
                    <th class="{{ $columnClass($column['key'], $column['money']) }}">{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($table['rows'] as $row)
                <tr>
                    @foreach ($table['columns'] as $column)
                        @php($value = $row[$column['key']] ?? null)
                        <td class="{{ $columnClass($column['key'], $column['money']) }}">
                            @if ($column['money'])
                                {{ $money($value) }}
                            @elseif ($column['key'] === 'paid_ratio')
                                {{ number_format((float) $value, 1) }}%
                            @else
                                {{ ($value === null || $value === '') ? '-' : $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($table['columns']), 1) }}" style="text-align:center" class="muted">
                        Sin datos para los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if (! empty($table['totals']) && count($table['rows']) > 0)
            <tfoot>
                <tr>
                    @foreach ($table['columns'] as $i => $column)
                        <td class="{{ $columnClass($column['key'], $column['money']) }}">
                            @if ($i === 0)
                                TOTAL
                            @elseif (array_key_exists($column['key'], $table['totals']))
                                {{ $column['money'] ? $money($table['totals'][$column['key']]) : $table['totals'][$column['key']] }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
@endif

<div class="footer">
    {{ $company?->name ?? config('app.name') }} - Documento generado automaticamente el {{ $generatedAt->format('d/m/Y H:i') }}
</div>
</body>
</html>

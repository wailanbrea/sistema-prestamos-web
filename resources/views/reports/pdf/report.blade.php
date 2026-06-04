<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1f2937; margin: 0; }
        .header { border-bottom: 2px solid #5e72e4; padding-bottom: 8px; margin-bottom: 12px; }
        .company { font-size: 16px; font-weight: bold; }
        .muted { color: #6b7280; }
        .title { font-size: 14px; font-weight: bold; margin: 10px 0 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 5px 6px; border: 1px solid #e5e7eb; }
        thead th { background: #eef0fb; text-align: left; font-size: 10px; text-transform: uppercase; }
        td.num, th.num { text-align: right; }
        tfoot td { background: #f3f4f6; font-weight: bold; }
        .summary td { border: 0; padding: 3px 6px; }
        .summary .label { color: #6b7280; }
        .footer { margin-top: 18px; border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
@php
    $money = fn ($v) => $currency.' '.number_format((float) $v, 2);
@endphp

<div class="header">
    <div class="company">{{ $company?->name ?? config('app.name') }}</div>
    @if ($company?->address)
        <div class="muted">{{ $company->address }} @if($company->phone) · {{ $company->phone }} @endif</div>
    @endif
    <div class="title">{{ $title }}</div>
    <div class="muted">
        Período: {{ $period['label'] ?? '—' }} ·
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
    <table>
        <thead>
            <tr>
                @foreach ($table['columns'] as $column)
                    <th class="{{ $column['money'] ? 'num' : '' }}">{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($table['rows'] as $row)
                <tr>
                    @foreach ($table['columns'] as $column)
                        @php($value = $row[$column['key']] ?? null)
                        <td class="{{ $column['money'] ? 'num' : '' }}">
                            @if ($column['money'])
                                {{ $money($value) }}
                            @elseif ($column['key'] === 'paid_ratio')
                                {{ number_format((float) $value, 1) }}%
                            @else
                                {{ ($value === null || $value === '') ? '—' : $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ max(count($table['columns']), 1) }}" style="text-align:center" class="muted">Sin datos para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
        @if (! empty($table['totals']) && count($table['rows']) > 0)
            <tfoot>
                <tr>
                    @foreach ($table['columns'] as $i => $column)
                        <td class="{{ $column['money'] ? 'num' : '' }}">
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
    {{ $company?->name ?? config('app.name') }} · Documento generado automáticamente el {{ $generatedAt->format('d/m/Y H:i') }}
</div>
</body>
</html>

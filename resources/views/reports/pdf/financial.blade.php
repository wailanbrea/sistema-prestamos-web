<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte financiero</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 22px; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
        .grid { display: table; width: 100%; margin-top: 14px; }
        .cell { display: table-cell; width: 33%; padding: 8px; border: 1px solid #e5e7eb; }
        .value { font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Reporte financiero</h1>
    <div class="muted">{{ $company?->name }} · {{ $report['period']['date_from'] }} a {{ $report['period']['date_to'] }}</div>

    <div class="grid">
        <div class="cell">
            <div class="muted">Cobrado</div>
            <div class="value">{{ currency() }} {{ number_format((float) $report['summary']['total_payments'], 2) }}</div>
        </div>
        <div class="cell">
            <div class="muted">Ganancia neta</div>
            <div class="value">{{ currency() }} {{ number_format((float) $report['summary']['net_profit'], 2) }}</div>
        </div>
        <div class="cell">
            <div class="muted">Capital activo</div>
            <div class="value">{{ currency() }} {{ number_format((float) $report['summary']['active_principal'], 2) }}</div>
        </div>
    </div>

    <h2>Resumen</h2>
    <table>
        <tbody>
            @foreach ($report['summary'] as $label => $value)
                <tr>
                    <td>{{ str_replace('_', ' ', ucfirst($label)) }}</td>
                    <td class="right">{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Cuotas atrasadas</h2>
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Préstamo</th>
                <th>Cuota</th>
                <th>Vencimiento</th>
                <th class="right">Pendiente</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['late_installments'] as $installment)
                <tr>
                    <td>{{ $installment->loan->client->full_name }}</td>
                    <td>{{ $installment->loan->loan_number }}</td>
                    <td>#{{ $installment->installment_number }}</td>
                    <td>{{ $installment->due_date->format('d/m/Y') }}</td>
                    <td class="right">{{ currency() }} {{ number_format((float) $installment->installment_amount + (float) $installment->late_fee - (float) $installment->total_paid, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No hay cuotas atrasadas.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

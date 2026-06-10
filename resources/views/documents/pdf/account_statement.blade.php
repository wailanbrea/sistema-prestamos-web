<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estado de cuenta</title>
@include('documents.pdf._styles')
@php($currencyCode = $loan->currency ?? currency())
</head>
<body>
    <h1>Estado de cuenta</h1>

    <table>
        <tr><th>Prestamo</th><td>{{ $loan->loan_number }}</td></tr>
        <tr><th>Cliente</th><td>{{ $loan->client->full_name }}</td></tr>
        <tr><th>Documento</th><td>{{ $loan->client->identification ?: 'N/A' }}</td></tr>
        <tr><th>Cobrador</th><td>{{ $loan->collector?->name ?: 'Sin cobrador' }}</td></tr>
        <tr><th>Estado</th><td>{{ config('loan_labels.loan_statuses.'.$loan->status.'.label', $loan->status) }}</td></tr>
    </table>

    <h2>Resumen financiero</h2>
    <table>
        <tr><th>Capital</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->principal_amount, 2) }}</td></tr>
        <tr><th>Interes total</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->total_interest, 2) }}</td></tr>
        <tr><th>Total del prestamo</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->total_amount, 2) }}</td></tr>
        <tr><th>Total cobrado</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->payments->sum('amount'), 2) }}</td></tr>
        <tr><th>Balance pendiente</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->remaining_balance, 2) }}</td></tr>
    </table>

    <h2>Cuotas</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Vencimiento</th>
                <th class="right">Cuota</th>
                <th class="right">Pagado</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($loan->installments as $installment)
                <tr>
                    <td>{{ $installment->installment_number }}</td>
                    <td>{{ $installment->due_date->format('d/m/Y') }}</td>
                    <td class="right">{{ $currencyCode }} {{ number_format((float) $installment->installment_amount, 2) }}</td>
                    <td class="right">{{ $currencyCode }} {{ number_format((float) $installment->total_paid, 2) }}</td>
                    <td>{{ config('loan_labels.installment_statuses.'.$installment->status.'.label', $installment->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Pagos aplicados</h2>
    <table>
        <thead>
            <tr>
                <th>Recibo</th>
                <th>Fecha</th>
                <th class="right">Monto</th>
                <th class="right">Capital</th>
                <th class="right">Interes</th>
                <th class="right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($loan->payments as $payment)
                <tr>
                    <td>{{ $payment->receipt_number }}</td>
                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                    <td class="right">{{ $currencyCode }} {{ number_format((float) $payment->amount, 2) }}</td>
                    <td class="right">{{ $currencyCode }} {{ number_format((float) $payment->principal_paid, 2) }}</td>
                    <td class="right">{{ $currencyCode }} {{ number_format((float) $payment->interest_paid, 2) }}</td>
                    <td class="right">{{ $currencyCode }} {{ number_format((float) $payment->new_balance, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center muted">No hay pagos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="muted center">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</p>
</body>
</html>

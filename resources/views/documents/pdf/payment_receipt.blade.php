<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recibo de pago</title>
    @include('documents.pdf._styles')
</head>
<body>
    <h1>Recibo de pago</h1>

    <div class="box">
        <strong>Recibo:</strong> {{ $payment->receipt_number }}<br>
        <strong>Cliente:</strong> {{ $payment->client->full_name }}<br>
        <strong>Préstamo:</strong> {{ $payment->loan->loan_number }}<br>
        <strong>Fecha de pago:</strong> {{ $payment->payment_date->format('d/m/Y') }}
    </div>

    <table>
        <tr><th>Monto recibido</th><td class="right">RD$ {{ number_format((float) $payment->amount, 2) }}</td></tr>
        <tr><th>Capital aplicado</th><td class="right">RD$ {{ number_format((float) $payment->principal_paid, 2) }}</td></tr>
        <tr><th>Interés aplicado</th><td class="right">RD$ {{ number_format((float) $payment->interest_paid, 2) }}</td></tr>
        <tr><th>Mora aplicada</th><td class="right">RD$ {{ number_format((float) $payment->late_fee_paid, 2) }}</td></tr>
        <tr><th>Balance anterior</th><td class="right">RD$ {{ number_format((float) $payment->previous_balance, 2) }}</td></tr>
        <tr><th>Balance nuevo</th><td class="right">RD$ {{ number_format((float) $payment->new_balance, 2) }}</td></tr>
    </table>

    <h2>Cuotas afectadas</h2>
    <table>
        <thead>
            <tr>
                <th>Cuota</th>
                <th class="right">Capital</th>
                <th class="right">Interés</th>
                <th class="right">Mora</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payment->details as $detail)
                <tr>
                    <td>#{{ $detail->installment->installment_number }}</td>
                    <td class="right">RD$ {{ number_format((float) $detail->principal_paid, 2) }}</td>
                    <td class="right">RD$ {{ number_format((float) $detail->interest_paid, 2) }}</td>
                    <td class="right">RD$ {{ number_format((float) $detail->late_fee_paid, 2) }}</td>
                    <td class="right">RD$ {{ number_format((float) $detail->amount_paid, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature">Recibido por<br>{{ $company->name }}</div>
    <div class="signature">Cliente<br>{{ $payment->client->full_name }}</div>
</body>
</html>

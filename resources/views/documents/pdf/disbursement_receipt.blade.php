<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de desembolso</title>
@include('documents.pdf._styles')
@php($currencyCode = $loan->currency ?? currency())
</head>
<body>
    <h1>Comprobante de desembolso</h1>

    <div class="box">
        <strong>Préstamo:</strong> {{ $loan->loan_number }}<br>
        <strong>Cliente:</strong> {{ $loan->client->full_name }}<br>
        <strong>Documento:</strong> {{ $loan->client->identification ?: 'N/A' }}<br>
        <strong>Fecha:</strong> {{ $loan->start_date->format('d/m/Y') }}
    </div>

    <p>
        Por medio del presente, <strong>{{ $company->name }}</strong> hace constar que ha desembolsado a
        <strong>{{ $loan->client->full_name }}</strong> la suma de
        <strong>{{ $currencyCode }} {{ number_format((float) $loan->principal_amount, 2) }}</strong>.
    </p>

    <table>
        <tr><th>Capital desembolsado</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->principal_amount, 2) }}</td></tr>
        <tr><th>Total a pagar</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->total_amount, 2) }}</td></tr>
        <tr><th>Frecuencia</th><td>{{ config('loan_labels.frequencies.'.$loan->payment_frequency, $loan->payment_frequency) }}</td></tr>
        <tr><th>Cantidad de cuotas</th><td>{{ $loan->term_quantity }}</td></tr>
    </table>

    <div class="signature">Recibido por<br>{{ $loan->client->full_name }}</div>
    <div class="signature">Entregado por<br>{{ $company->name }}</div>
</body>
</html>

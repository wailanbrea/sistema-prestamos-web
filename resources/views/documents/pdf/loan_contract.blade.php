<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Contrato de prestamo</title>
@include('documents.pdf._styles')
@php($currencyCode = $loan->currency ?? currency())
</head>
<body>
    <h1>Contrato de prestamo</h1>

    <p>
        Entre <strong>{{ $company->name }}</strong>, en calidad de prestamista, y
        <strong>{{ $loan->client->full_name }}</strong>, identificado con
        <strong>{{ $loan->client->identification ?: 'N/A' }}</strong>, se formaliza el prestamo
        <strong>{{ $loan->loan_number }}</strong> por un capital de
        <strong>{{ $currencyCode }} {{ number_format((float) $loan->principal_amount, 2) }}</strong>.
    </p>

    <div class="box">
        <strong>Direccion del cliente:</strong> {{ $loan->client->address ?: 'N/A' }}<br>
        <strong>Telefono:</strong> {{ $loan->client->phone ?: 'N/A' }}<br>
        <strong>Fecha de inicio:</strong> {{ $loan->start_date->format('d/m/Y') }}<br>
        <strong>Primer pago:</strong> {{ $loan->first_payment_date->format('d/m/Y') }}
    </div>

    <h2>Condiciones del prestamo</h2>
    <table>
        <tr><th>Metodo de calculo</th><td>{{ config('loan_labels.methods.'.$loan->calculation_method, $loan->calculation_method) }}</td></tr>
        <tr><th>Frecuencia</th><td>{{ config('loan_labels.frequencies.'.$loan->payment_frequency, $loan->payment_frequency) }}</td></tr>
        <tr><th>Cantidad de cuotas</th><td>{{ $loan->term_quantity }}</td></tr>
        <tr><th>Tasa</th><td>{{ rtrim(rtrim(number_format((float) $loan->interest_rate, 4, '.', ''), '0'), '.') ?: '0' }}%</td></tr>
        <tr><th>Interes total</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->total_interest, 2) }}</td></tr>
        <tr><th>Total a pagar</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->total_amount, 2) }}</td></tr>
        <tr><th>Cuota programada</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->installment_amount, 2) }}</td></tr>
    </table>

    <h2>Calendario pactado</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Vencimiento</th>
                <th class="right">Capital</th>
                <th class="right">Interes</th>
                <th class="right">Cuota</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($loan->installments as $installment)
                <tr>
                    <td>{{ $installment->installment_number }}</td>
                    <td>{{ $installment->due_date->format('d/m/Y') }}</td>
                    <td class="right">{{ $currencyCode }} {{ number_format((float) $installment->principal_amount, 2) }}</td>
                    <td class="right">{{ $currencyCode }} {{ number_format((float) $installment->interest_amount, 2) }}</td>
                    <td class="right">{{ $currencyCode }} {{ number_format((float) $installment->installment_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>
        El cliente se compromete a pagar cada cuota en las fechas establecidas. Cualquier atraso podra generar
        cargos adicionales segun la configuracion del prestamo y las politicas internas de la empresa.
    </p>

    <div class="signature">Cliente<br>{{ $loan->client->full_name }}</div>
    <div class="signature">Prestamista<br>{{ $company->name }}</div>

    <p class="muted center">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</p>
</body>
</html>

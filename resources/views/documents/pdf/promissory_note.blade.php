<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Pagaré notarial</title>
    @include('documents.pdf._styles')
</head>
<body>
    <h1>Pagaré notarial</h1>

    <p>
        Yo, <strong>{{ $loan->client->full_name }}</strong>, identificado con documento
        <strong>{{ $loan->client->identification ?: 'N/A' }}</strong>, domiciliado en
        <strong>{{ $loan->client->address ?: 'N/A' }}</strong>, reconozco adeudar a
        <strong>{{ $company->name }}</strong> la suma de
        <strong>RD$ {{ number_format((float) $loan->total_amount, 2) }}</strong>, correspondiente al préstamo
        <strong>{{ $loan->loan_number }}</strong>.
    </p>

    <p>
        El monto será pagado conforme al calendario de cuotas pactado, con frecuencia
        <strong>{{ $loan->payment_frequency }}</strong>, en un plazo de
        <strong>{{ $loan->term_quantity }}</strong> cuotas, iniciando el
        <strong>{{ $loan->first_payment_date->format('d/m/Y') }}</strong>.
    </p>

    <h2>Condiciones financieras</h2>
    <table>
        <tr><th>Capital</th><td class="right">RD$ {{ number_format((float) $loan->principal_amount, 2) }}</td></tr>
        <tr><th>Interés total</th><td class="right">RD$ {{ number_format((float) $loan->total_interest, 2) }}</td></tr>
        <tr><th>Total a pagar</th><td class="right">RD$ {{ number_format((float) $loan->total_amount, 2) }}</td></tr>
        <tr><th>Cuota</th><td class="right">RD$ {{ number_format((float) $loan->installment_amount, 2) }}</td></tr>
    </table>

    <p>
        En caso de atraso, se aplicarán los cargos de mora pactados en el préstamo. Este documento se emite
        como soporte operativo para formalización legal y puede ser complementado por cláusulas notariales
        específicas según la jurisdicción aplicable.
    </p>

    <div class="signature">Deudor<br>{{ $loan->client->full_name }}</div>
    <div class="signature">Acreedor<br>{{ $company->name }}</div>

    <p class="muted center">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</p>
</body>
</html>

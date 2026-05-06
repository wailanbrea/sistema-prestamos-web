<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Carta de saldo</title>
    @include('documents.pdf._styles')
</head>
<body>
    <h1>Carta de saldo</h1>

    <p>
        <strong>{{ $company->name }}</strong> certifica que el cliente
        <strong>{{ $loan->client->full_name }}</strong>, identificado con documento
        <strong>{{ $loan->client->identification ?: 'N/A' }}</strong>, ha saldado completamente el préstamo
        <strong>{{ $loan->loan_number }}</strong>.
    </p>

    <table>
        <tr><th>Capital original</th><td class="right">RD$ {{ number_format((float) $loan->principal_amount, 2) }}</td></tr>
        <tr><th>Total pagado a capital</th><td class="right">RD$ {{ number_format((float) $loan->paid_principal, 2) }}</td></tr>
        <tr><th>Total pagado a interés</th><td class="right">RD$ {{ number_format((float) $loan->paid_interest, 2) }}</td></tr>
        <tr><th>Total pagado a mora</th><td class="right">RD$ {{ number_format((float) $loan->paid_late_fee, 2) }}</td></tr>
        <tr><th>Balance pendiente</th><td class="right">RD$ {{ number_format((float) $loan->remaining_balance, 2) }}</td></tr>
    </table>

    <p>
        Esta carta se emite a solicitud de la parte interesada en fecha
        <strong>{{ $generatedAt->format('d/m/Y') }}</strong>.
    </p>

    <div class="signature">Autorizado por<br>{{ $company->name }}</div>
</body>
</html>

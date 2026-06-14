<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $contract->contract_number }}</title>
@include('documents.pdf._styles')
    <style>
        .header-grid { width: 100%; margin-bottom: 6px; }
        .header-grid td { border: 0; padding: 0; vertical-align: top; }
        .qr-cell { width: 140px; text-align: right; }
        .qr-cell img { width: 110px; height: 110px; }
        .parties td { width: 50%; vertical-align: top; }
        .clause { margin: 10px 0; text-align: justify; }
        .clause strong { display: block; margin-bottom: 2px; }
        .sign-img { max-height: 70px; max-width: 220px; }
        .verify { font-size: 10px; color: #6b7280; word-break: break-all; }
        .badge { display: inline-block; padding: 2px 8px; border: 1px solid #111827; border-radius: 10px; font-size: 10px; }
    </style>
@php($currencyCode = $loan->currency ?? currency())
@php($lateLabels = ['none' => 'Sin mora', 'fixed' => 'Monto fijo por cuota atrasada', 'daily_percentage' => 'Porcentaje diario sobre la cuota', 'daily_fixed' => 'Monto fijo por día de atraso'])
</head>
<body>
    <table class="header-grid">
        <tr>
            <td>
                <h1 style="text-align:left; margin-bottom:4px;">{{ $titleHeading ?? 'Contrato de préstamo' }}</h1>
                <div class="muted">No. {{ $contract->contract_number }} · <span class="badge">{{ strtoupper($contract->status) }}</span></div>
            </td>
            <td class="qr-cell">
                <img src="{{ $qrSvg }}" alt="QR verificación">
                <div class="verify center">Verificación</div>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td>
                <strong>PRESTAMISTA</strong><br>
                {{ $company->name }}<br>
                @if ($company->rnc)RNC/Cédula: {{ $company->rnc }}<br>@endif
                @if ($company->address)Dirección: {{ $company->address }}<br>@endif
                @if ($company->phone)Tel: {{ $company->phone }}@endif
            </td>
            <td>
                <strong>CLIENTE (DEUDOR)</strong><br>
                {{ $loan->client->full_name }}<br>
                Identificación: {{ $loan->client->identification ?: 'N/A' }}<br>
                @if ($loan->client->address)Dirección: {{ $loan->client->address }}<br>@endif
                @if ($loan->client->phone)Tel: {{ $loan->client->phone }}@endif
            </td>
        </tr>
    </table>

    <p class="clause">
        Por el presente documento, <strong style="display:inline">{{ $company->name }}</strong> (el "Prestamista") otorga al
        cliente <strong style="display:inline">{{ $loan->client->full_name }}</strong> (el "Deudor") un préstamo identificado como
        <strong style="display:inline">{{ $loan->loan_number }}</strong> por un capital de
        <strong style="display:inline">{{ $currencyCode }} {{ number_format((float) $loan->principal_amount, 2) }}</strong>,
        bajo las condiciones que se detallan a continuación, las cuales el Deudor declara conocer y aceptar.
    </p>

    <h2>Condiciones del préstamo</h2>
    <table>
        <tr><th>Capital</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->principal_amount, 2) }}</td></tr>
        <tr><th>Método de cálculo</th><td>{{ config('loan_labels.methods.'.$loan->calculation_method, $loan->calculation_method) }}</td></tr>
        <tr><th>Frecuencia de pago</th><td>{{ config('loan_labels.frequencies.'.$loan->payment_frequency, $loan->payment_frequency) }}</td></tr>
        <tr><th>Cantidad de cuotas</th><td>{{ $loan->term_quantity }}</td></tr>
        <tr><th>Tasa de interés</th><td>{{ rtrim(rtrim(number_format((float) $loan->interest_rate, 4, '.', ''), '0'), '.') ?: '0' }}%</td></tr>
        <tr><th>Interés total</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->total_interest, 2) }}</td></tr>
        <tr><th>Total a pagar</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->total_amount, 2) }}</td></tr>
        <tr><th>Cuota</th><td class="right">{{ $currencyCode }} {{ number_format((float) $loan->installment_amount, 2) }}</td></tr>
        <tr><th>Fecha de inicio</th><td>{{ $loan->start_date->format('d/m/Y') }}</td></tr>
        <tr><th>Primer pago</th><td>{{ $loan->first_payment_date->format('d/m/Y') }}</td></tr>
    </table>

    <h2>Tabla de pagos</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Vencimiento</th>
                <th class="right">Capital</th>
                <th class="right">Interés</th>
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

    <h2>Cláusulas</h2>
    <div class="clause">
        <strong>PRIMERA — Mora.</strong>
        En caso de atraso en el pago de cualquier cuota, el Deudor pagará un cargo por mora según la modalidad
        <em>{{ $lateLabels[$loan->late_fee_type] ?? $loan->late_fee_type }}</em>@if ($loan->late_fee_type !== 'none') por un valor de
        <strong style="display:inline">{{ in_array($loan->late_fee_type, ['daily_percentage']) ? rtrim(rtrim(number_format((float) $loan->late_fee_value, 4, '.', ''), '0'), '.').'%' : $currencyCode.' '.number_format((float) $loan->late_fee_value, 2) }}</strong>@endif,
        sin perjuicio de las demás obligaciones aquí contraídas.
    </div>
    <div class="clause">
        <strong>SEGUNDA — Incumplimiento.</strong>
        La falta de pago de una o más cuotas faculta al Prestamista a considerar vencido el plazo de la totalidad
        de la deuda y a exigir el pago íntegro del saldo pendiente de capital, intereses y moras acumuladas.
    </div>
    <div class="clause">
        <strong>TERCERA — Costos legales.</strong>
        Todos los gastos de cobranza, honorarios legales y costas judiciales o extrajudiciales que se generen por
        el incumplimiento del Deudor serán por cuenta exclusiva de este último.
    </div>
    <div class="clause">
        <strong>CUARTA — Aceptación.</strong>
        El Deudor declara haber leído y comprendido la totalidad de las condiciones y cláusulas de este contrato, y
        manifiesta su aceptación libre y voluntaria mediante su firma electrónica, la cual reconoce como válida y
        vinculante para todos los efectos legales.
    </div>

    <h2>Firmas</h2>
    <table style="margin-top:4px;">
        <tr>
            <td class="center" style="width:50%;">
                @if ($signature)
                    <img class="sign-img" src="{{ $signatureImage ?? '' }}" alt="Firma del cliente"><br>
                    <span class="muted">{{ $signature->signer_name }}</span><br>
                    <span class="muted">Firmado el {{ optional($signature->signed_at)->format('d/m/Y H:i') }}</span>
                @else
                    <div style="height:70px;"></div>
                    <span class="muted">Pendiente de firma</span>
                @endif
                <div style="border-top:1px solid #111827; margin-top:6px; padding-top:4px;">Cliente (Deudor)</div>
            </td>
            <td class="center" style="width:50%;">
                <div style="height:70px;"></div>
                <div style="border-top:1px solid #111827; margin-top:6px; padding-top:4px;">{{ $company->name }} (Prestamista)</div>
            </td>
        </tr>
    </table>

    @if ($signature)
        <div class="box" style="font-size:10px;">
            <strong>Evidencia de firma electrónica:</strong>
            IP {{ $signature->ip_address ?: 'N/D' }} ·
            Dispositivo {{ $signature->device_type ?: 'N/D' }} ({{ $signature->platform ?: 'N/D' }}, {{ $signature->browser ?: 'N/D' }})
            @if ($signature->latitude && $signature->longitude) · GPS {{ $signature->latitude }}, {{ $signature->longitude }} @endif
        </div>
    @endif

    <p class="verify">
        Código de verificación: {{ $contract->uuid }}<br>
        Hash SHA-256: {{ $contentHash }}<br>
        Verifique la autenticidad en: {{ $verifyUrl }}
    </p>
    <p class="muted center">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</p>
</body>
</html>

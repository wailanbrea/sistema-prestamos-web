<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contrato firmado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .shell { min-height: 100vh; display: grid; place-items: center; padding: 18px; }
        .card-ok { width: min(520px, 100%); border: 0; border-radius: 18px; box-shadow: 0 20px 50px rgba(17, 24, 39, .12); text-align: center; }
        .check { width: 72px; height: 72px; border-radius: 50%; background: #e8f8f0; color: #12b76a; display: grid; place-items: center; margin: 0 auto 14px; font-size: 34px; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="card card-ok">
            <div class="card-body p-4 p-lg-5">
                <div class="check">&#10004;</div>
                <h1 class="h4 fw-bold mb-2">¡Contrato firmado!</h1>
                <p class="text-muted">Tu contrato <strong>{{ $contract->contract_number }}</strong> ha sido firmado correctamente.
                    Guardamos tu firma y la evidencia de aceptación.</p>
                @if ($contract->isSigned())
                    <a href="{{ $downloadUrl }}" target="_blank" class="btn btn-primary w-100 mt-2">Descargar contrato firmado</a>
                @endif
            </div>
        </div>
    </div>
</body>
</html>

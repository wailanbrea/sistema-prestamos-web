<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación de contrato</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .shell { min-height: 100vh; display: grid; place-items: center; padding: 18px; }
        .card-v { width: min(560px, 100%); border: 0; border-radius: 18px; box-shadow: 0 20px 50px rgba(17, 24, 39, .12); }
        .hash { font-family: monospace; font-size: 11px; word-break: break-all; color: #6b7280; }
        .row-v { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #e5e7eb; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="card card-v">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h5 fw-bold mb-3">Verificación de autenticidad</h1>

                @if ($hashMatches && $contract->isSigned())
                    <div class="alert alert-success">Contrato <strong>auténtico y firmado</strong>. La integridad del contenido coincide.</div>
                @elseif ($hashMatches)
                    <div class="alert alert-info">Contrato <strong>auténtico</strong>. Integridad verificada. Estado actual: {{ strtoupper($contract->status) }}.</div>
                @else
                    <div class="alert alert-danger">No se pudo verificar la integridad del contenido. El documento pudo haber sido alterado.</div>
                @endif

                <div class="row-v"><span class="text-muted">Contrato</span><strong>{{ $contract->contract_number }}</strong></div>
                <div class="row-v"><span class="text-muted">Cliente</span><strong>{{ $contract->loan->client->full_name }}</strong></div>
                <div class="row-v"><span class="text-muted">Préstamo</span><strong>{{ $contract->loan->loan_number }}</strong></div>
                <div class="row-v"><span class="text-muted">Estado</span><strong>{{ strtoupper($contract->status) }}</strong></div>
                @if ($contract->signed_at)
                    <div class="row-v"><span class="text-muted">Firmado el</span><strong>{{ $contract->signed_at->format('d/m/Y H:i') }}</strong></div>
                @endif
                <div class="mt-3">
                    <div class="text-muted small mb-1">Hash SHA-256 del contenido</div>
                    <div class="hash">{{ $contract->hash_sha256 }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

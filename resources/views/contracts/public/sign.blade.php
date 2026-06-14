<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Firmar contrato {{ $contract->contract_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .sign-shell { min-height: 100vh; display: grid; place-items: center; padding: 18px; }
        .sign-card { width: min(720px, 100%); border: 0; border-radius: 18px; box-shadow: 0 20px 50px rgba(17, 24, 39, .12); }
        .summary-item { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #e5e7eb; }
        .summary-item:last-child { border-bottom: 0; }
        .pad-wrap { position: relative; border: 2px dashed #c7ccd6; border-radius: 14px; background: #fff; touch-action: none; }
        #signaturePad { width: 100%; height: 220px; display: block; border-radius: 14px; }
        .pad-hint { position: absolute; left: 50%; top: 50%; transform: translate(-50%,-50%); color: #aeb4be; pointer-events: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="sign-shell">
        <div class="card sign-card">
            <div class="card-body p-4 p-lg-5">
                <div class="mb-3">
                    <h1 class="h4 fw-bold mb-1">Contrato de préstamo</h1>
                    <p class="text-muted mb-0">No. {{ $contract->contract_number }} · {{ $company->name }}</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <div class="border rounded-3 p-3 mb-3">
                    <div class="summary-item"><span class="text-muted">Cliente</span><strong>{{ $loan->client->full_name }}</strong></div>
                    <div class="summary-item"><span class="text-muted">Préstamo</span><strong>{{ $loan->loan_number }}</strong></div>
                    <div class="summary-item"><span class="text-muted">Capital</span><strong>{{ $loan->currency }} {{ number_format((float) $loan->principal_amount, 2) }}</strong></div>
                    <div class="summary-item"><span class="text-muted">Total a pagar</span><strong>{{ $loan->currency }} {{ number_format((float) $loan->total_amount, 2) }}</strong></div>
                    <div class="summary-item"><span class="text-muted">Cuotas</span><strong>{{ $loan->term_quantity }} · {{ $loan->currency }} {{ number_format((float) $loan->installment_amount, 2) }}</strong></div>
                </div>

                <a href="{{ $downloadUrl }}" target="_blank" class="btn btn-outline-secondary w-100 mb-3">
                    Ver contrato completo (PDF)
                </a>

                <form id="sign-form" method="POST" action="{{ route('contracts.public.sign', $contract->uuid) }}">
                    @csrf
                    <input type="hidden" name="signature" id="signature-input">
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">

                    <div class="mb-3">
                        <label for="signer_name" class="form-label">Nombre de quien firma</label>
                        <input type="text" id="signer_name" name="signer_name" class="form-control" value="{{ old('signer_name', $loan->client->full_name) }}" maxlength="180" required>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="accepted_terms" id="accepted_terms" value="1">
                        <label class="form-check-label" for="accepted_terms">He leído y acepto los términos y condiciones del contrato.</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="accepted_legal" id="accepted_legal" value="1">
                        <label class="form-check-label" for="accepted_legal">Reconozco que esta firma representa mi aceptación legal.</label>
                    </div>

                    <label class="form-label">Firma</label>
                    <div class="pad-wrap mb-2">
                        <canvas id="signaturePad"></canvas>
                        <span class="pad-hint" id="pad-hint">Firma aquí con tu dedo</span>
                    </div>
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" id="clear-btn" class="btn btn-outline-secondary flex-fill">Limpiar</button>
                        <button type="submit" id="submit-btn" class="btn btn-primary flex-fill">Firmar contrato</button>
                    </div>
                    <p class="text-muted small mb-0">Al firmar se registrará la fecha, hora y datos del dispositivo como evidencia legal.</p>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script>
        (function () {
            const canvas = document.getElementById('signaturePad');
            const hint = document.getElementById('pad-hint');
            const ratio = Math.max(window.devicePixelRatio || 1, 1);

            function resize() {
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
            }
            window.addEventListener('resize', () => { const d = pad.toData(); resize(); pad.fromData(d); });
            resize();

            const pad = new SignaturePad(canvas, { penColor: '#0a2540', backgroundColor: 'rgba(255,255,255,0)' });
            pad.addEventListener('beginStroke', () => { hint.style.display = 'none'; });

            document.getElementById('clear-btn').addEventListener('click', () => { pad.clear(); hint.style.display = 'block'; });

            // Captura de ubicación opcional (no bloquea la firma).
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (pos) {
                    document.getElementById('latitude').value = pos.coords.latitude;
                    document.getElementById('longitude').value = pos.coords.longitude;
                }, function () {}, { enableHighAccuracy: false, timeout: 8000 });
            }

            document.getElementById('sign-form').addEventListener('submit', function (e) {
                if (pad.isEmpty()) {
                    e.preventDefault();
                    alert('Por favor dibuja tu firma antes de continuar.');
                    return;
                }
                if (!document.getElementById('accepted_terms').checked || !document.getElementById('accepted_legal').checked) {
                    e.preventDefault();
                    alert('Debes aceptar ambas casillas para firmar.');
                    return;
                }
                document.getElementById('signature-input').value = pad.toDataURL('image/png');
                document.getElementById('submit-btn').disabled = true;
                document.getElementById('submit-btn').textContent = 'Procesando...';
            });
        })();
    </script>
</body>
</html>

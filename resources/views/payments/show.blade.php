@extends('layouts.app')

@section('title', $payment->receipt_number.' - '.config('app.name'))

@section('content')
    @php($paymentCurrency = $payment->loan->currency ?? currency())
    <section class="mb-4 payment-receipt-header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">{{ $payment->receipt_number }}</h1>
                <p class="text-muted mb-0">{{ $payment->client->full_name }} · {{ $payment->loan->loan_number }}</p>
                <div class="mt-2">@include('partials.status-badge', ['map' => 'payment_statuses', 'value' => $payment->status])</div>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 no-print">
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fa-solid fa-print me-2"></i> Imprimir recibo
                </button>
                @if (session('paymentReceiptUrl'))
                    <a href="{{ session('paymentReceiptUrl') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-file-arrow-down me-2"></i> Descargar PDF
                    </a>
                @endif
                @can('payments.create')
                    <a href="{{ route('payments.create', ['loan_id' => $payment->loan_id]) }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-plus me-2"></i> Otro cobro
                    </a>
                @endcan
                @if ($payment->status === 'valid' && ($payment->client->phone || $payment->client->secondary_phone))
                    <a href="{{ route('payments.whatsapp', $payment) }}" class="btn btn-outline-success">
                        <i class="fa-brands fa-whatsapp me-2"></i> Enviar recibo
                    </a>
                @endif
                @can('payments.cancel')
                    @if ($payment->status === 'valid')
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#cancelPaymentForm">
                            <i class="fa-solid fa-ban me-2"></i> Anular
                        </button>
                    @endif
                @endcan
            </div>
        </div>
    </section>

    @if (session('paymentReceiptUrl'))
        <section class="alert alert-success d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 no-print">
            <div>
                <div class="fw-semibold">Cobro registrado correctamente.</div>
                <div class="small mb-0">Puedes imprimir el recibo ahora o descargar el PDF generado.</div>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <button type="button" class="btn btn-success" onclick="window.print()">
                    <i class="fa-solid fa-print me-2"></i> Imprimir ahora
                </button>
                <a href="{{ session('paymentReceiptUrl') }}" target="_blank" rel="noopener" class="btn btn-outline-success">
                    <i class="fa-solid fa-file-pdf me-2"></i> Abrir PDF
                </a>
            </div>
        </section>
    @endif

    @if ($payment->status === 'cancelled')
        <section class="alert alert-danger">
            <div class="fw-semibold">Cobro anulado</div>
            <div>{{ $payment->cancellation_reason }}</div>
            <div class="small">Por {{ $payment->cancelledBy?->name ?: 'Sistema' }} el {{ $payment->cancelled_at?->format('d/m/Y H:i') }}</div>
        </section>
    @endif

    @can('payments.cancel')
        @if ($payment->status === 'valid')
            <section id="cancelPaymentForm" class="collapse mb-4 no-print">
                <div class="card border-danger">
                    <div class="card-body">
                        <h2 class="h6 text-danger text-uppercase mb-3">Anular cobro</h2>
                        <form method="POST" action="{{ route('payments.cancel', $payment) }}">
                            @csrf
                            <label for="cancellation_reason" class="form-label">Motivo</label>
                            <textarea id="cancellation_reason" name="cancellation_reason" rows="3" class="form-control @error('cancellation_reason') is-invalid @enderror" required>{{ old('cancellation_reason') }}</textarea>
                            @error('cancellation_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('¿Confirmas la anulación de este cobro?');">
                                    Confirmar anulación
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        @endif
    @endcan

    <section class="row g-4 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Monto recibido</div>
                    <div class="fs-3 fw-bold">{{ $paymentCurrency }} {{ number_format((float) $payment->amount, 2) }}</div>
                    <div class="text-muted">@include('payments.partials.method-label', ['method' => $payment->payment_method]) · {{ $payment->payment_date->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Distribución</div>
                    <div>Tipo aplicado: <strong>@include('payments.partials.allocation-label', ['payment' => $payment])</strong></div>
                    @if ($payment->targetInstallment)
                        <div class="text-muted small">Cuota objetivo: #{{ $payment->targetInstallment->installment_number }}</div>
                    @endif
                    <div>Capital: {{ $paymentCurrency }} {{ number_format((float) $payment->principal_paid, 2) }}</div>
                    <div>Interés: {{ $paymentCurrency }} {{ number_format((float) $payment->interest_paid, 2) }}</div>
                    <div>Mora: {{ $paymentCurrency }} {{ number_format((float) $payment->late_fee_paid, 2) }}</div>
                    @if ((float) $payment->capital_prepaid > 0)
                        <div class="text-primary">Abono a capital: {{ $paymentCurrency }} {{ number_format((float) $payment->capital_prepaid, 2) }}</div>
                    @endif
                    @if ((float) $payment->change_given > 0)
                        <div class="text-success">Vuelto al cliente: {{ $paymentCurrency }} {{ number_format((float) $payment->change_given, 2) }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Balance</div>
                    <div>Anterior: {{ $paymentCurrency }} {{ number_format((float) $payment->previous_balance, 2) }}</div>
                    <div>Nuevo: {{ $paymentCurrency }} {{ number_format((float) $payment->new_balance, 2) }}</div>
                    <div>Cobrador: {{ $payment->collector?->name ?: 'Sin cobrador' }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Cuotas afectadas</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Cuota</th>
                            <th>Vencimiento</th>
                            <th class="text-end">Capital</th>
                            <th class="text-end">Interés</th>
                            <th class="text-end">Interes pendiente</th>
                            <th class="text-end">Mora</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payment->details as $detail)
                            <tr>
                                <td>#{{ $detail->installment?->installment_number ?? 'N/D' }}</td>
                                <td>{{ $detail->installment?->due_date?->format('d/m/Y') ?? 'N/D' }}</td>
                                <td class="text-end">{{ $paymentCurrency }} {{ number_format((float) $detail->principal_paid, 2) }}</td>
                                <td class="text-end">{{ $paymentCurrency }} {{ number_format((float) $detail->interest_paid, 2) }}</td>
                                <td class="text-end">{{ $paymentCurrency }} {{ number_format($detail->installment ? max(0, (float) $detail->installment->interest_amount - (float) $detail->installment->paid_interest) : 0, 2) }}</td>
                                <td class="text-end">{{ $paymentCurrency }} {{ number_format((float) $detail->late_fee_paid, 2) }}</td>
                                <td class="text-end">{{ $paymentCurrency }} {{ number_format((float) $detail->amount_paid, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @if (session('generatedWhatsappUrl'))
        <script>
            window.addEventListener('load', () => {
                window.open(@json(session('generatedWhatsappUrl')), '_blank', 'noopener');
            });
        </script>
    @endif
@endsection

@push('styles')
    <style>
        @media print {
            @page {
                margin: 12mm;
            }

            body {
                background: #fff !important;
                color: #000 !important;
                font-size: 12px;
            }

            .sidebar,
            .sidebar-backdrop,
            .topbar,
            .no-print {
                display: none !important;
            }

            .app-shell {
                display: block !important;
                min-height: auto !important;
            }

            .main {
                padding: 0 !important;
            }

            .content-card,
            .card {
                border: 1px solid #d6d6d6 !important;
                box-shadow: none !important;
                break-inside: avoid;
            }

            .payment-receipt-header {
                border-bottom: 1px solid #d6d6d6;
                margin-bottom: 16px !important;
                padding-bottom: 12px;
            }

            .row {
                --bs-gutter-x: .75rem;
                --bs-gutter-y: .75rem;
            }

            .table {
                font-size: 11px;
            }
        }
    </style>
@endpush

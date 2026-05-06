@extends('layouts.app')

@section('title', $payment->receipt_number.' - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">{{ $payment->receipt_number }}</h1>
                <p class="text-muted mb-0">{{ $payment->client->full_name }} · {{ $payment->loan->loan_number }}</p>
                @if ($payment->status === 'cancelled')
                    <span class="badge text-bg-danger mt-2">Anulado</span>
                @else
                    <span class="badge text-bg-success mt-2">Válido</span>
                @endif
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('payments.create', ['loan_id' => $payment->loan_id]) }}" class="btn btn-outline-primary">
                    <i class="fa-solid fa-plus me-2"></i> Otro cobro
                </a>
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

    @if ($payment->status === 'cancelled')
        <section class="alert alert-danger">
            <div class="fw-semibold">Cobro anulado</div>
            <div>{{ $payment->cancellation_reason }}</div>
            <div class="small">Por {{ $payment->cancelledBy?->name ?: 'Sistema' }} el {{ $payment->cancelled_at?->format('d/m/Y H:i') }}</div>
        </section>
    @endif

    @can('payments.cancel')
        @if ($payment->status === 'valid')
            <section id="cancelPaymentForm" class="collapse mb-4">
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
                    <div class="fs-3 fw-bold">RD$ {{ number_format((float) $payment->amount, 2) }}</div>
                    <div class="text-muted">@include('payments.partials.method-label', ['method' => $payment->payment_method]) · {{ $payment->payment_date->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Distribución</div>
                    <div>Capital: RD$ {{ number_format((float) $payment->principal_paid, 2) }}</div>
                    <div>Interés: RD$ {{ number_format((float) $payment->interest_paid, 2) }}</div>
                    <div>Mora: RD$ {{ number_format((float) $payment->late_fee_paid, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Balance</div>
                    <div>Anterior: RD$ {{ number_format((float) $payment->previous_balance, 2) }}</div>
                    <div>Nuevo: RD$ {{ number_format((float) $payment->new_balance, 2) }}</div>
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
                            <th class="text-end">Mora</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payment->details as $detail)
                            <tr>
                                <td>#{{ $detail->installment->installment_number }}</td>
                                <td>{{ $detail->installment->due_date->format('d/m/Y') }}</td>
                                <td class="text-end">RD$ {{ number_format((float) $detail->principal_paid, 2) }}</td>
                                <td class="text-end">RD$ {{ number_format((float) $detail->interest_paid, 2) }}</td>
                                <td class="text-end">RD$ {{ number_format((float) $detail->late_fee_paid, 2) }}</td>
                                <td class="text-end">RD$ {{ number_format((float) $detail->amount_paid, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

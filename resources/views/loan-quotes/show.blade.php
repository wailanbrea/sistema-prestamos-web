@extends('layouts.app')

@include('loan-quotes.partials.labels')

@section('title', 'Cotización COT-'.str_pad((string) $quote->id, 5, '0', STR_PAD_LEFT).' - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Cotización COT-{{ str_pad((string) $quote->id, 5, '0', STR_PAD_LEFT) }}</h1>
                <p class="text-muted mb-0">{{ $quote->client?->full_name ?? 'Sin cliente asignado' }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('loan-quotes.index') }}" class="btn btn-outline-secondary">Volver</a>
                @if ($quote->client_id && $quote->status !== 'converted')
                    <a href="{{ route('loans.create', ['quote_id' => $quote->id]) }}" class="btn btn-primary">
                        <i class="fa-solid fa-file-invoice-dollar me-2"></i> Convertir a préstamo
                    </a>
                @else
                    <button type="button" class="btn btn-primary" disabled>
                        <i class="fa-solid fa-file-invoice-dollar me-2"></i> Convertir a préstamo
                    </button>
                @endif
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <article class="card metric-card"><div class="card-body"><div class="text-muted small">Monto</div><div class="h4 fw-bold mb-0">RD$ {{ number_format((float) $quote->amount, 2) }}</div></div></article>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <article class="card metric-card"><div class="card-body"><div class="text-muted small">Cuota</div><div class="h4 fw-bold mb-0">RD$ {{ number_format((float) $quote->installment_amount, 2) }}</div></div></article>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <article class="card metric-card"><div class="card-body"><div class="text-muted small">Interés total</div><div class="h4 fw-bold mb-0">RD$ {{ number_format((float) $quote->total_interest, 2) }}</div></div></article>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <article class="card metric-card"><div class="card-body"><div class="text-muted small">Total a pagar</div><div class="h4 fw-bold mb-0">RD$ {{ number_format((float) $quote->total_to_pay, 2) }}</div></div></article>
        </div>
    </section>

    <section class="row g-3">
        <div class="col-12 col-xl-8">
            <article class="card content-card">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Tabla de amortización</h2>
                    <p class="text-muted small mb-0">Proyección calculada para esta cotización.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th class="text-end">Capital</th>
                                    <th class="text-end">Interés</th>
                                    <th class="text-end">Cuota</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($calculation['installments'] as $installment)
                                    <tr>
                                        <td>{{ $installment['number'] }}</td>
                                        <td class="text-end">RD$ {{ number_format((float) $installment['principal'], 2) }}</td>
                                        <td class="text-end">RD$ {{ number_format((float) $installment['interest'], 2) }}</td>
                                        <td class="text-end fw-semibold">RD$ {{ number_format((float) $installment['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-12 col-xl-4">
            <article class="card content-card">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Condiciones</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Estado</span><span class="badge {{ $statusLabels[$quote->status]['class'] ?? 'text-bg-secondary' }}">{{ $statusLabels[$quote->status]['label'] ?? $quote->status }}</span></div>
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Frecuencia</span><strong>{{ $frequencyLabels[$quote->payment_frequency] ?? $quote->payment_frequency }}</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Método</span><strong>{{ $methodLabels[$quote->calculation_method] ?? $quote->calculation_method }}</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-3"><span class="text-muted">Tasa</span><strong>{{ number_format((float) $quote->interest_rate, 4) }}%</strong></div>
                    <div class="d-flex justify-content-between py-3"><span class="text-muted">Cuotas</span><strong>{{ $quote->term_quantity }}</strong></div>
                </div>
            </article>
        </div>
    </section>
@endsection

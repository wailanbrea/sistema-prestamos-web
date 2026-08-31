@extends('layouts.app')

@section('title', 'Mi cartera - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <h1 class="h3 fw-bold mb-1">Mi cartera</h1>
        <p class="text-muted mb-0">Préstamos y recibos asignados a {{ $collector->name }}.</p>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="card content-card h-100"><div class="card-body"><div class="text-muted small text-uppercase">Préstamos activos</div><div class="fs-3 fw-bold text-primary">{{ number_format($metrics['active_loans']) }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card content-card h-100"><div class="card-body"><div class="text-muted small text-uppercase">En mora</div><div class="fs-3 fw-bold text-danger">{{ number_format($metrics['late_loans']) }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card content-card h-100"><div class="card-body"><div class="text-muted small text-uppercase">Cuotas pendientes</div><div class="fs-3 fw-bold text-warning">{{ number_format($metrics['pending_installments']) }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card content-card h-100"><div class="card-body"><div class="text-muted small text-uppercase">Balance asignado</div><div class="fs-5 fw-bold text-primary">{{ currency() }} {{ number_format($metrics['remaining_balance'], 2) }}</div></div></div></div>
    </section>

    <section class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card content-card h-100">
                <div class="card-header bg-white border-0 pt-3"><h2 class="h6 fw-bold mb-0">Préstamos asignados</h2></div>
                <div class="card-body pt-2">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Préstamo</th><th>Cliente</th><th>Estado</th><th class="text-end">Balance</th></tr></thead>
                            <tbody>
                                @forelse ($loans as $loan)
                                    <tr>
                                        <td class="fw-semibold">{{ $loan->loan_number }}</td>
                                        <td>{{ $loan->client?->full_name ?? 'Cliente no disponible' }}</td>
                                        <td><span class="badge {{ $loan->status === 'late' ? 'text-bg-danger' : 'text-bg-success' }}">{{ $loan->status === 'late' ? 'En mora' : 'Activo' }}</span></td>
                                        <td class="text-end">{{ $loan->currency ?? currency() }} {{ number_format((float) $loan->remaining_balance, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">No tienes préstamos asignados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card content-card h-100">
                <div class="card-header bg-white border-0 pt-3"><h2 class="h6 fw-bold mb-0">Recibos recientes</h2></div>
                <div class="card-body pt-2">
                    <div class="vstack gap-2">
                        @forelse ($recentPayments as $payment)
                            <a href="{{ route('payments.show', $payment) }}" class="d-flex justify-content-between align-items-center gap-3 border rounded-3 p-3 text-decoration-none">
                                <span><span class="d-block fw-semibold text-dark">{{ $payment->client?->full_name ?? 'Cliente no disponible' }}</span><span class="text-muted small">{{ $payment->receipt_number }} · {{ $payment->loan?->loan_number }}</span></span>
                                <span class="text-end"><strong class="d-block text-success">{{ $payment->loan?->currency ?? currency() }} {{ number_format((float) $payment->amount, 2) }}</strong><span class="text-muted small">{{ $payment->payment_date->format('d/m/Y') }}</span></span>
                            </a>
                        @empty
                            <div class="text-center text-muted py-4">No tienes recibos registrados.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

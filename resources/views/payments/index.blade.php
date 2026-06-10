@extends('layouts.app')

@section('title', 'Cobros - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Cobros</h1>
                <p class="text-muted mb-0">Registro de pagos, distribución a cuotas, caja y comisiones.</p>
            </div>
            @can('payments.create')
                <a href="{{ route('payments.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-2"></i> Nuevo cobro
                </a>
            @endcan
        </div>
    </section>

    <section class="card content-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('payments.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="search" class="form-label">Buscar</label>
                    <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Recibo, préstamo o cliente">
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="payment_method" class="form-label">Método</label>
                    <select id="payment_method" name="payment_method" class="form-select">
                        <option value="">Todos</option>
                        @foreach (config('loan_labels.payment_methods') as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['payment_method'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label for="date_from" class="form-label">Desde</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label for="date_to" class="form-label">Hasta</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>
                <div class="col-12 col-lg-1 d-grid">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Recibo</th>
                            <th>Cliente</th>
                            <th>Préstamo</th>
                            <th>Método</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end">Balance nuevo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            @php($paymentCurrency = $payment->loan->currency ?? currency())
                            <tr>
                                <td>
                                    <a href="{{ route('payments.show', $payment) }}" class="fw-semibold text-decoration-none">{{ $payment->receipt_number }}</a>
                                    <div class="text-muted small">{{ $payment->payment_date->format('d/m/Y') }}</div>
                                </td>
                                <td>{{ $payment->client->full_name }}</td>
                                <td>{{ $payment->loan->loan_number }}</td>
                                <td>@include('payments.partials.method-label', ['method' => $payment->payment_method])</td>
                                <td class="text-end">{{ $paymentCurrency }} {{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="text-end">{{ $paymentCurrency }} {{ number_format((float) $payment->new_balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">No hay cobros registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $payments->links() }}
            </div>
        </div>
    </section>
@endsection

@extends('layouts.app')

@section('title', 'Reportes - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Reportes</h1>
                <p class="text-muted mb-0">Atrasos, ganancias, cartera por cliente y rendimiento por cobrador.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('reports.financial.pdf', $filters) }}" class="btn btn-outline-danger">
                    <i class="fa-solid fa-file-pdf me-2"></i> PDF
                </a>
                <a href="{{ route('reports.financial.csv', $filters) }}" class="btn btn-outline-success">
                    <i class="fa-solid fa-file-csv me-2"></i> CSV
                </a>
            </div>
        </div>
    </section>

    <section class="card content-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="date_from" class="form-label">Desde</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $report['period']['date_from'] }}" class="form-control">
                </div>
                <div class="col-12 col-md-4">
                    <label for="date_to" class="form-label">Hasta</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $report['period']['date_to'] }}" class="form-control">
                </div>
                <div class="col-12 col-md-4 d-grid">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-filter me-2"></i> Aplicar
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="row g-4 mb-4">
        @foreach ([
            ['Cobrado', 'total_payments', 'text-success'],
            ['Ganancia bruta', 'gross_profit', 'text-primary'],
            ['Gastos', 'total_expenses', 'text-danger'],
            ['Ganancia neta', 'net_profit', 'text-dark'],
            ['Capital activo', 'active_principal', 'text-dark'],
            ['Desembolsado', 'disbursed_in_period', 'text-dark'],
        ] as [$label, $key, $class])
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card content-card h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">{{ $label }}</div>
                        <div class="fs-3 fw-bold {{ $class }}">@include('reports.partials.money', ['amount' => $report['summary'][$key]])</div>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Rendimiento por cobrador</h2>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Cobrador</th>
                                    <th class="text-end">Cobros</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Ganancia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($report['by_collector'] as $row)
                                    <tr>
                                        <td>{{ $row->name }}</td>
                                        <td class="text-end">{{ $row->payments_count }}</td>
                                        <td class="text-end">@include('reports.partials.money', ['amount' => $row->total_collected])</td>
                                        <td class="text-end">@include('reports.partials.money', ['amount' => (float) $row->interest_collected + (float) $row->late_fee_collected])</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No hay cobros por cobrador en el período.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card content-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Cartera por cliente</h2>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th class="text-end">Préstamos</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($report['by_client'] as $row)
                                    <tr>
                                        <td>
                                            {{ $row->full_name }}
                                            <div class="text-muted small">{{ $row->phone ?: 'Sin teléfono' }}</div>
                                        </td>
                                        <td class="text-end">{{ $row->loans_count }}</td>
                                        <td class="text-end">@include('reports.partials.money', ['amount' => $row->remaining_balance])</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No hay cartera activa.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="card content-card">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Cuotas atrasadas</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Préstamo</th>
                            <th>Cuota</th>
                            <th>Vencimiento</th>
                            <th>Cobrador</th>
                            <th class="text-end">Pendiente</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['late_installments'] as $installment)
                            <tr>
                                <td>
                                    {{ $installment->loan->client->full_name }}
                                    <div class="text-muted small">{{ $installment->loan->client->phone ?: 'Sin teléfono' }}</div>
                                </td>
                                <td>{{ $installment->loan->loan_number }}</td>
                                <td>#{{ $installment->installment_number }}</td>
                                <td>{{ $installment->due_date->format('d/m/Y') }}</td>
                                <td>{{ $installment->loan->collector?->name ?: 'Sin cobrador' }}</td>
                                <td class="text-end">
                                    @include('reports.partials.money', [
                                        'amount' => (float) $installment->installment_amount + (float) $installment->late_fee - (float) $installment->total_paid,
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No hay cuotas atrasadas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

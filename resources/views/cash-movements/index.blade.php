@extends('layouts.app')

@section('title', 'Caja - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Caja</h1>
                <p class="text-muted mb-0">Consolidado de entradas, salidas y balance operativo.</p>
            </div>
            <a href="{{ route('cash-movements.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-2"></i> Movimiento manual
            </a>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-md-4">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Entradas</div>
                    <div class="fs-3 fw-bold text-success">RD$ {{ number_format($totals['total_in'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Salidas</div>
                    <div class="fs-3 fw-bold text-danger">RD$ {{ number_format($totals['total_out'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card content-card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Balance</div>
                    <div class="fs-3 fw-bold">RD$ {{ number_format($totals['balance'], 2) }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="card content-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('cash-movements.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label for="type" class="form-label">Tipo</label>
                    <select id="type" name="type" class="form-select">
                        <option value="">Todos</option>
                        @foreach (['loan_disbursement', 'payment_received', 'expense', 'collector_commission', 'capital_injection', 'capital_withdrawal', 'adjustment'] as $type)
                            <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>@include('cash-movements.partials.type-label', ['type' => $type])</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label for="direction" class="form-label">Dirección</label>
                    <select id="direction" name="direction" class="form-select">
                        <option value="">Todas</option>
                        <option value="in" @selected(($filters['direction'] ?? '') === 'in')>Entrada</option>
                        <option value="out" @selected(($filters['direction'] ?? '') === 'out')>Salida</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="date_from" class="form-label">Desde</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label for="date_to" class="form-label">Hasta</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-filter me-2"></i> Filtrar
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
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Usuario</th>
                            <th class="text-end">Entrada</th>
                            <th class="text-end">Salida</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td>{{ $movement->movement_date->format('d/m/Y') }}</td>
                                <td>@include('cash-movements.partials.type-label', ['type' => $movement->type])</td>
                                <td>{{ $movement->description ?: 'Sin descripción' }}</td>
                                <td>{{ $movement->createdBy?->name ?: 'Sistema' }}</td>
                                <td class="text-end text-success">
                                    {{ $movement->direction === 'in' ? 'RD$ '.number_format((float) $movement->amount, 2) : '-' }}
                                </td>
                                <td class="text-end text-danger">
                                    {{ $movement->direction === 'out' ? 'RD$ '.number_format((float) $movement->amount, 2) : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">No hay movimientos de caja.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $movements->links() }}
            </div>
        </div>
    </section>
@endsection

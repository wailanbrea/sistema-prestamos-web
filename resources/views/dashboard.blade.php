@extends('layouts.app')

@section('title', 'Dashboard - '.config('app.name'))

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Dashboard financiero</h1>
                <p class="text-muted mb-0">Resumen operativo y estado de inversión de la empresa.</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-calendar-days me-2"></i> Este mes
                </button>
                <button type="button" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-2"></i> Nuevo préstamo
                </button>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4">
        @php
            $cards = [
                ['label' => 'Capital prestado', 'value' => $metrics['capital_prestado'], 'icon' => 'fa-sack-dollar', 'bg' => 'bg-primary', 'money' => true],
                ['label' => 'Cobros del día', 'value' => $metrics['cobros_hoy'], 'icon' => 'fa-cash-register', 'bg' => 'bg-success', 'money' => true],
                ['label' => 'Ganancia neta', 'value' => $metrics['ganancia_neta'], 'icon' => 'fa-chart-line', 'bg' => 'bg-info', 'money' => true],
                ['label' => 'Gastos del mes', 'value' => $metrics['gastos_mes'], 'icon' => 'fa-receipt', 'bg' => 'bg-danger', 'money' => true],
                ['label' => 'Préstamos activos', 'value' => $metrics['prestamos_activos'], 'icon' => 'fa-file-invoice-dollar', 'bg' => 'bg-warning', 'money' => false],
                ['label' => 'Préstamos en mora', 'value' => $metrics['prestamos_mora'], 'icon' => 'fa-triangle-exclamation', 'bg' => 'bg-dark', 'money' => false],
                ['label' => 'Clientes atrasados', 'value' => $metrics['clientes_atrasados'], 'icon' => 'fa-user-clock', 'bg' => 'bg-secondary', 'money' => false],
                ['label' => 'Sin coordenadas', 'value' => $metrics['clientes_sin_coordenadas'], 'icon' => 'fa-map-location-dot', 'bg' => 'bg-danger', 'money' => false],
                ['label' => 'Cobradores activos', 'value' => $metrics['cobradores_activos'], 'icon' => 'fa-motorcycle', 'bg' => 'bg-primary', 'money' => false],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="card metric-card h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">{{ $card['label'] }}</div>
                            <div class="h4 fw-bold mb-0">
                                @if ($card['money'])
                                    RD$ {{ number_format((float) $card['value'], 2) }}
                                @else
                                    {{ number_format((int) $card['value']) }}
                                @endif
                            </div>
                        </div>
                        <span class="metric-icon {{ $card['bg'] }}">
                            <i class="fa-solid {{ $card['icon'] }}"></i>
                        </span>
                    </div>
                </article>
            </div>
        @endforeach
    </section>

    <section class="row g-3">
        <div class="col-12 col-xl-8">
            <article class="card content-card h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Cobros recientes</h2>
                    <p class="text-muted small mb-0">Aquí se mostrará la actividad de cobros cuando el módulo esté activo.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Recibo</th>
                                    <th>Cliente</th>
                                    <th>Cobrador</th>
                                    <th class="text-end">Monto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">No hay cobros registrados todavía.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-12 col-xl-4">
            <article class="card content-card h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Estado de inversión</h2>
                    <p class="text-muted small mb-0">Indicadores base para monitorear capital y rendimiento.</p>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-3">
                        <span class="text-muted">Capital invertido</span>
                        <strong>RD$ {{ number_format((float) $metrics['capital_invertido'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-3">
                        <span class="text-muted">Capital disponible</span>
                        <strong>RD$ {{ number_format((float) $metrics['capital_disponible'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-3">
                        <span class="text-muted">Intereses generados</span>
                        <strong>RD$ {{ number_format((float) $metrics['intereses_generados'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-3">
                        <span class="text-muted">Préstamos saldados</span>
                        <strong>{{ number_format((int) $metrics['prestamos_saldados']) }}</strong>
                    </div>
                </div>
            </article>
        </div>
    </section>
@endsection

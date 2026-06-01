@extends('layouts.app')

@section('title', 'Dashboard - '.config('app.name'))

@section('content')
    <section class="mb-4 anim-fade-up">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">Dashboard financiero</h1>
                <p class="text-muted mb-0">Resumen operativo y estado de inversión de la empresa.</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-calendar-days me-2"></i> Este mes
                </button>
                @can('loans.create')
                    <a href="{{ route('loans.create') }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus me-2"></i> Nuevo préstamo
                    </a>
                @endcan
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4">
        @php
            // 'feature' = menú del que depende la tarjeta; se oculta si el plan no lo incluye.
            $cards = [
                ['label' => 'Capital prestado', 'value' => $metrics['capital_prestado'], 'icon' => 'fa-sack-dollar', 'bg' => 'bg-primary', 'money' => true],
                ['label' => 'Cobros del día', 'value' => $metrics['cobros_hoy'], 'icon' => 'fa-cash-register', 'bg' => 'bg-success', 'money' => true],
                ['label' => 'Ganancia neta', 'value' => $metrics['ganancia_neta'], 'icon' => 'fa-chart-line', 'bg' => 'bg-info', 'money' => true],
                ['label' => 'Gastos del mes', 'value' => $metrics['gastos_mes'], 'icon' => 'fa-receipt', 'bg' => 'bg-danger', 'money' => true, 'feature' => 'expenses.index'],
                ['label' => 'Préstamos activos', 'value' => $metrics['prestamos_activos'], 'icon' => 'fa-file-invoice-dollar', 'bg' => 'bg-warning', 'money' => false],
                ['label' => 'Préstamos en mora', 'value' => $metrics['prestamos_mora'], 'icon' => 'fa-triangle-exclamation', 'bg' => 'bg-dark', 'money' => false],
                ['label' => 'Clientes atrasados', 'value' => $metrics['clientes_atrasados'], 'icon' => 'fa-user-clock', 'bg' => 'bg-secondary', 'money' => false],
                ['label' => 'Sin coordenadas', 'value' => $metrics['clientes_sin_coordenadas'], 'icon' => 'fa-map-location-dot', 'bg' => 'bg-danger', 'money' => false, 'feature' => 'routes.map'],
                ['label' => 'Cobradores activos', 'value' => $metrics['cobradores_activos'], 'icon' => 'fa-motorcycle', 'bg' => 'bg-primary', 'money' => false, 'feature' => 'collectors.index'],
            ];

            $cards = array_values(array_filter($cards, fn ($card) => empty($card['feature']) || \App\Support\MenuAccess::planAllowsMenu(auth()->user(), $card['feature'])));
        @endphp

        @foreach ($cards as $index => $card)
            <div class="col-12 col-sm-6 col-xl-3">
                <article class="card metric-card h-100 anim-fade-up" style="animation-delay: {{ $index * 60 }}ms;">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">{{ $card['label'] }}</div>
                            <div class="h4 fw-bold mb-0 metric-value"
                                 data-count-target="{{ (float) $card['value'] }}"
                                 data-count-money="{{ $card['money'] ? '1' : '0' }}">
                                @if ($card['money'])
                                    {{ currency() }} {{ number_format((float) $card['value'], 2) }}
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

    <section class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
            <article class="card content-card h-100 anim-fade-up">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between flex-wrap gap-2 pb-0">
                    <div>
                        <h2 class="h6 fw-bold mb-1">Tendencia de cobros</h2>
                        <p class="text-muted small mb-0">Monto cobrado en los últimos 14 días.</p>
                    </div>
                    <span class="badge text-bg-light border"><i class="fa-solid fa-coins me-1 text-success"></i> {{ currency() }}</span>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 280px;">
                        <canvas id="collectionsChart"></canvas>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-12 col-xl-4">
            <article class="card content-card h-100 anim-fade-up">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Cartera de préstamos</h2>
                    <p class="text-muted small mb-0">Distribución por estado.</p>
                </div>
                <div class="card-body d-flex flex-column">
                    @php $hasLoans = array_sum($loanDistribution) > 0; @endphp
                    @if ($hasLoans)
                        <div style="position: relative; height: 220px;">
                            <canvas id="loanDistributionChart"></canvas>
                        </div>
                        <div class="d-flex justify-content-around text-center mt-3 small">
                            <div><span class="d-block fw-bold h5 mb-0 text-success">{{ $loanDistribution['active'] }}</span><span class="text-muted">Activos</span></div>
                            <div><span class="d-block fw-bold h5 mb-0 text-danger">{{ $loanDistribution['late'] }}</span><span class="text-muted">En mora</span></div>
                            <div><span class="d-block fw-bold h5 mb-0 text-secondary">{{ $loanDistribution['paid'] }}</span><span class="text-muted">Saldados</span></div>
                        </div>
                    @else
                        <div class="text-center text-muted py-5 my-auto">
                            <i class="fa-solid fa-folder-open fa-2x mb-3 d-block opacity-50"></i>
                            No hay préstamos registrados todavía.
                        </div>
                    @endif
                </div>
            </article>
        </div>
    </section>

    <section class="row g-3">
        <div class="col-12 col-xl-8">
            <article class="card content-card h-100 anim-fade-up">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Cobros recientes</h2>
                    <p class="text-muted small mb-0">Últimos pagos válidos registrados.</p>
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
                                    <th class="text-end">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentPayments as $payment)
                                    <tr>
                                        <td><span class="fw-semibold">{{ $payment->receipt_number ?? '—' }}</span></td>
                                        <td>{{ $payment->client?->full_name ?? 'Cliente eliminado' }}</td>
                                        <td><span class="text-muted">{{ $payment->collector?->name ?? '—' }}</span></td>
                                        <td class="text-end fw-semibold">{{ currency() }} {{ number_format((float) $payment->amount, 2) }}</td>
                                        <td class="text-end text-muted small">{{ $payment->payment_date->isoFormat('DD MMM YYYY') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="fa-solid fa-receipt fa-2x mb-3 d-block opacity-50"></i>
                                            No hay cobros registrados todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-12 col-xl-4">
            <article class="card content-card h-100 anim-fade-up">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h6 fw-bold mb-1">Estado de inversión</h2>
                    <p class="text-muted small mb-0">Indicadores base para monitorear capital y rendimiento.</p>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-3">
                        <span class="text-muted"><i class="fa-solid fa-vault me-2 text-primary"></i>Capital invertido</span>
                        <strong>{{ currency() }} {{ number_format((float) $metrics['capital_invertido'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-3">
                        <span class="text-muted"><i class="fa-solid fa-wallet me-2 text-success"></i>Capital disponible</span>
                        <strong>{{ currency() }} {{ number_format((float) $metrics['capital_disponible'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-3">
                        <span class="text-muted"><i class="fa-solid fa-percent me-2 text-info"></i>Intereses generados</span>
                        <strong>{{ currency() }} {{ number_format((float) $metrics['intereses_generados'], 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-3">
                        <span class="text-muted"><i class="fa-solid fa-circle-check me-2 text-secondary"></i>Préstamos saldados</span>
                        <strong>{{ number_format((int) $metrics['prestamos_saldados']) }}</strong>
                    </div>
                </div>
            </article>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    (function () {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // --- Animated count-up for metric cards ---
        const formatValue = (value, isMoney) => isMoney
            ? @json(currency().' ') +value.toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : Math.round(value).toLocaleString('es-DO');

        document.querySelectorAll('[data-count-target]').forEach((el) => {
            const target = parseFloat(el.dataset.countTarget) || 0;
            const isMoney = el.dataset.countMoney === '1';
            if (reduceMotion || target === 0) {
                el.textContent = formatValue(target, isMoney);
                return;
            }
            const duration = 900;
            const start = performance.now();
            const step = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = formatValue(target * eased, isMoney);
                if (progress < 1) {
                    requestAnimationFrame(step);
                }
            };
            requestAnimationFrame(step);
        });

        if (typeof Chart === 'undefined') {
            return;
        }
        Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
        Chart.defaults.color = '#67748e';

        // --- Collections trend (line) ---
        const trendEl = document.getElementById('collectionsChart');
        if (trendEl) {
            const ctx = trendEl.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 280);
            gradient.addColorStop(0, 'rgba(94, 114, 228, .35)');
            gradient.addColorStop(1, 'rgba(94, 114, 228, 0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($collectionsTrend['labels']),
                    datasets: [{
                        label: 'Cobrado',
                        data: @json($collectionsTrend['values']),
                        borderColor: '#5e72e4',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: .4,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#5e72e4',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: reduceMotion ? false : { duration: 900 },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (c) => @json(currency().' ') +c.parsed.y.toLocaleString('es-DO', { minimumFractionDigits: 2 }),
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,.05)' },
                            ticks: { callback: (v) => @json(currency().' ') +v.toLocaleString('es-DO') },
                        },
                        x: { grid: { display: false } },
                    },
                },
            });
        }

        // --- Loan distribution (donut) ---
        const donutEl = document.getElementById('loanDistributionChart');
        if (donutEl) {
            const dist = @json($loanDistribution);
            new Chart(donutEl, {
                type: 'doughnut',
                data: {
                    labels: ['Activos', 'En mora', 'Saldados'],
                    datasets: [{
                        data: [dist.active, dist.late, dist.paid],
                        backgroundColor: ['#2dce89', '#f5365c', '#adb5bd'],
                        borderWidth: 0,
                        hoverOffset: 8,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    animation: reduceMotion ? false : { animateRotate: true, duration: 900 },
                    plugins: { legend: { display: false } },
                },
            });
        }
    })();
</script>
@endpush

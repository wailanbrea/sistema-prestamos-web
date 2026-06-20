@extends('layouts.app')

@section('title', 'Dashboard — '.config('app.name'))

@push('styles')
<style>
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 14px;
    }
    @media (max-width:575.98px) {
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .kpi-tile {
        padding: 18px 16px; border-radius: 16px;
        display: flex; flex-direction: column; justify-content: space-between;
        min-height: 110px;
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .kpi-tile:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,38,83,.1); }
    .kpi-tile:active { transform: scale(.98); }
    .kpi-tile-primary  { background:var(--app-primary-tint); color:#fff; }
    .kpi-tile-amber    { background:var(--app-secondary); color:var(--app-on-secondary); }
    .kpi-tile-white    { background:var(--app-surface); border:1px solid var(--app-border-light); }
    .kpi-tile-surface  { background:var(--app-surface-low); border:1px solid var(--app-border-light); }

    .kpi-tile .kpi-label { font-size:.75rem; font-weight:500; opacity:.8; margin-bottom:6px; }
    .kpi-tile .kpi-value { font-size:1.6rem; font-weight:700; line-height:1.2; font-variant-numeric:tabular-nums; }
    .kpi-tile .kpi-badge { font-size:.7rem; font-weight:600; padding:2px 8px; border-radius:99px; background:rgba(255,255,255,.15); display:inline-block; margin-top:10px; }

    .chart-bar-wrap { display:flex; align-items:flex-end; justify-content:space-between; height:180px; gap:10px; }
    .chart-bar-item { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px; }
    .chart-bar-track { width:100%; border-radius:8px 8px 0 0; position:relative; overflow:hidden; background:var(--app-surface-high); }
    .chart-bar-fill  { position:absolute; bottom:0; left:0; right:0; background:var(--app-primary); border-radius:8px 8px 0 0; transition:height .6s cubic-bezier(.16,1,.3,1); }
    .chart-bar-label { font-size:.7rem; color:var(--app-muted); }
    .chart-bar-label.active { color:var(--app-primary); font-weight:700; border-bottom:2px solid var(--app-primary); padding-bottom:2px; }

    .alert-critical {
        background:var(--app-error-bg);
        border:1px solid rgba(186,26,26,.2);
        border-radius:16px;
        color:var(--app-error);
        padding:14px 18px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
    }

    /* Tabs */
    .dash-tabs { display:flex; border-bottom:1px solid var(--app-border-light); padding:0 20px; gap:4px; }
    .dash-tab {
        padding:12px 16px; font-size:.85rem; font-weight:500; color:var(--app-muted);
        border:none; background:none; cursor:pointer; position:relative;
        border-bottom:2px solid transparent; margin-bottom:-1px;
        transition:color .15s ease;
    }
    .dash-tab:hover { color:var(--app-primary); }
    .dash-tab.active { color:var(--app-primary); font-weight:600; border-bottom-color:var(--app-primary); }
    .dash-tab i { margin-right:6px; }

    .loan-item {
        display:flex; align-items:center; justify-content:space-between;
        padding:14px 16px; border-radius:14px;
        background:var(--app-surface-low);
        transition:background .15s ease;
    }
    .loan-item:hover { background:var(--app-surface-mid); }
    .loan-avatar {
        width:44px; height:44px; border-radius:99px;
        display:inline-grid; place-items:center;
        font-weight:700; font-size:.95rem;
        flex-shrink:0;
    }
    .status-pill {
        font-size:.65rem; font-weight:700; text-transform:uppercase;
        letter-spacing:.05em; padding:2px 8px; border-radius:99px;
    }
    .status-pill-active  { background:#dcfce7; color:#166534; }
    .status-pill-late    { background:#fef9c3; color:#854d0e; }
    .status-pill-overdue { background:#fee2e2; color:#991b1b; }
    .status-pill-paid    { background:var(--app-surface-high); color:var(--app-muted); }

    .invest-row { display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid var(--app-border-light); }
    .invest-row:last-child { border-bottom:none; }
    .invest-row .invest-icon { width:32px; height:32px; border-radius:8px; display:inline-grid; place-items:center; font-size:.8rem; flex-shrink:0; }
</style>
@endpush

@section('content')

{{-- ── Page header ── --}}
<section class="mb-4 anim-fade-up">
    <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-3">
        <div>
            <h1 class="h4 fw-bold mb-1" style="color:var(--app-primary);">Dashboard financiero</h1>
            <p class="text-muted mb-0" style="font-size:.88rem;">Resumen operativo y estado de inversión de la empresa.</p>
        </div>
        <div class="d-flex gap-2">
            @can('loans.create')
                <a href="{{ route('loans.create') }}" class="btn btn-sm fw-semibold"
                   style="background:var(--app-secondary); color:var(--app-on-secondary); border-radius:10px; border:none; padding:8px 16px;">
                    <i class="fa-solid fa-plus me-2"></i> Nuevo préstamo
                </a>
            @endcan
        </div>
    </div>
</section>

{{-- ── Critical alert ── --}}
@if (isset($metrics['prestamos_mora']) && $metrics['prestamos_mora'] > 0)
<div class="alert-critical mb-4 anim-fade-up" style="animation-delay:60ms;">
    <div class="d-flex align-items-start gap-3">
        <i class="fa-solid fa-triangle-exclamation mt-1" style="color:var(--app-error);"></i>
        <div>
            <div class="fw-bold" style="font-size:.88rem;">Alerta: Préstamos en mora</div>
            <div style="font-size:.82rem; opacity:.85; margin-top:2px;">
                Hay {{ number_format($metrics['prestamos_mora']) }} préstamos con pagos vencidos.
            </div>
        </div>
    </div>
    @can('payments.view')
        <a href="{{ route('payments.index') }}" class="btn btn-sm fw-bold text-nowrap"
           style="background:var(--app-error); color:#fff; border:none; border-radius:8px; font-size:.78rem; padding:6px 14px;">
            Revisar
        </a>
    @endcan
</div>
@endif

{{-- ── KPI scroll strip ── --}}
<section class="mb-4 anim-fade-up" style="animation-delay:80ms;">
    <div class="d-flex justify-content-between align-items-center px-1 mb-3">
        <h2 class="h6 fw-bold mb-0" style="color:var(--app-primary);">Resumen Financiero</h2>
    </div>
    <div class="kpi-grid">
        <div class="kpi-tile kpi-tile-primary">
            <i class="fa-solid fa-sack-dollar" style="opacity:.7; font-size:1.1rem;"></i>
            <div>
                <div class="kpi-label">Capital prestado</div>
                <div class="kpi-value">{{ number_format((float)$metrics['capital_prestado'], 0) }}</div>
                <div style="font-size:.7rem; opacity:.7; margin-top:2px;">{{ currency() }}</div>
            </div>
        </div>
        <div class="kpi-tile kpi-tile-white">
            <i class="fa-solid fa-wallet" style="color:var(--app-primary); opacity:.7; font-size:1.1rem;"></i>
            <div>
                @php
                    $capitalNegativo = (float) $metrics['capital_disponible'] < 0;
                @endphp
                <div class="kpi-label" style="color:var(--app-muted);">Capital disponible</div>
                <div class="kpi-value" style="color:{{ $capitalNegativo ? 'var(--bs-danger, #dc3545)' : 'var(--app-primary)' }};">{{ number_format((float)$metrics['capital_disponible'], 0) }}</div>
                <div style="font-size:.7rem; color:{{ $capitalNegativo ? 'var(--bs-danger, #dc3545)' : 'var(--app-muted)' }}; margin-top:2px;">{{ currency() }}@if ($capitalNegativo) · registra una inyección de capital en Caja @endif</div>
            </div>
        </div>
        <div class="kpi-tile kpi-tile-amber">
            <i class="fa-solid fa-cash-register" style="opacity:.7; font-size:1.1rem;"></i>
            <div>
                <div class="kpi-label">Cobros del día</div>
                <div class="kpi-value">{{ number_format((float)$metrics['cobros_hoy'], 0) }}</div>
                <div style="font-size:.7rem; opacity:.7; margin-top:2px;">{{ currency() }}</div>
            </div>
        </div>
        <div class="kpi-tile kpi-tile-surface">
            <i class="fa-solid fa-chart-line" style="color:var(--app-primary); opacity:.7; font-size:1.1rem;"></i>
            <div>
                <div class="kpi-label" style="color:var(--app-muted);">Ganancia neta</div>
                <div class="kpi-value" style="color:var(--app-primary);">{{ number_format((float)$metrics['ganancia_neta'], 0) }}</div>
                <div style="font-size:.7rem; color:var(--app-muted); margin-top:2px;">{{ currency() }}</div>
            </div>
        </div>
        <div class="kpi-tile kpi-tile-surface">
            <i class="fa-solid fa-file-invoice-dollar" style="color:var(--app-primary); opacity:.7; font-size:1.1rem;"></i>
            <div>
                <div class="kpi-label" style="color:var(--app-muted);">Préstamos activos</div>
                <div class="kpi-value" style="color:var(--app-primary);">{{ number_format((int)$metrics['prestamos_activos']) }}</div>
                <div style="font-size:.7rem; color:var(--app-muted); margin-top:2px;">en cartera</div>
            </div>
        </div>
        @if(\App\Support\MenuAccess::planAllowsMenu(auth()->user(), 'expenses.index'))
        <div class="kpi-tile kpi-tile-surface">
            <i class="fa-solid fa-receipt" style="color:var(--app-error); opacity:.8; font-size:1.1rem;"></i>
            <div>
                <div class="kpi-label" style="color:var(--app-muted);">Gastos del mes</div>
                <div class="kpi-value" style="color:var(--app-error);">{{ number_format((float)$metrics['gastos_mes'], 0) }}</div>
                <div style="font-size:.7rem; color:var(--app-muted); margin-top:2px;">{{ currency() }}</div>
            </div>
        </div>
        @endif
    </div>
</section>

{{-- ── Charts row ── --}}
<section class="row g-3 mb-4">
    <div class="col-12 col-xl-8">
        <article class="card content-card h-100 anim-fade-up" style="animation-delay:120ms;">
            <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between flex-wrap gap-2 pb-0 pt-3 px-4">
                <div>
                    <h3 class="h6 fw-bold mb-1" style="color:var(--app-primary);">Cobros de la semana</h3>
                    <p class="text-muted small mb-0">Monto cobrado en los últimos 14 días.</p>
                </div>
                <span class="badge text-bg-light border" style="font-size:.7rem;">
                    <i class="fa-solid fa-coins me-1 text-success"></i> {{ currency() }}
                </span>
            </div>
            <div class="card-body pt-3">
                <div style="position:relative; height:260px;">
                    <canvas id="collectionsChart"></canvas>
                </div>
            </div>
        </article>
    </div>

    <div class="col-12 col-xl-4">
        <article class="card content-card h-100 anim-fade-up" style="animation-delay:160ms;">
            <div class="card-header bg-white border-0 pb-0 pt-3 px-4">
                <h3 class="h6 fw-bold mb-1" style="color:var(--app-primary);">Cartera de préstamos</h3>
                <p class="text-muted small mb-0">Distribución por estado.</p>
            </div>
            <div class="card-body d-flex flex-column">
                @if (array_sum($loanDistribution) > 0)
                    <div style="position:relative; height:200px;">
                        <canvas id="loanDistributionChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-around text-center mt-3 small">
                        <div>
                            <span class="d-block fw-bold h5 mb-0" style="color:#166534;">{{ $loanDistribution['active'] }}</span>
                            <span class="text-muted">Activos</span>
                        </div>
                        <div>
                            <span class="d-block fw-bold h5 mb-0" style="color:var(--app-error);">{{ $loanDistribution['late'] }}</span>
                            <span class="text-muted">En mora</span>
                        </div>
                        <div>
                            <span class="d-block fw-bold h5 mb-0 text-secondary">{{ $loanDistribution['paid'] }}</span>
                            <span class="text-muted">Saldados</span>
                        </div>
                    </div>
                @else
                    <div class="text-center text-muted py-5 my-auto">
                        <i class="fa-solid fa-folder-open fa-2x mb-3 d-block opacity-40"></i>
                        No hay préstamos registrados.
                    </div>
                @endif
            </div>
        </article>
    </div>
</section>

{{-- ── Recent payments + investment status ── --}}
<section class="row g-3">
    <div class="col-12 col-xl-8">
        <article class="card content-card h-100 anim-fade-up" style="animation-delay:200ms;">
            {{-- Tab header --}}
            <div class="d-flex align-items-center justify-content-between pt-3 px-4 pb-0">
                <div class="dash-tabs flex-grow-1 px-0">
                    <button class="dash-tab active" data-tab="cobros">
                        <i class="fa-solid fa-cash-register"></i> Cobros recientes
                    </button>
                    <button class="dash-tab" data-tab="prestamos">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Últimos préstamos
                    </button>
                </div>
                <div class="flex-shrink-0 ps-3 pb-1 d-flex gap-2">
                    @can('payments.view')
                        <a href="{{ route('payments.index') }}" id="dash-link-cobros"
                           class="btn btn-link btn-sm text-decoration-none fw-semibold p-0"
                           style="font-size:.82rem; color:var(--app-primary);">Ver todos</a>
                    @endcan
                    @can('loans.view')
                        <a href="{{ route('loans.index') }}" id="dash-link-prestamos"
                           class="btn btn-link btn-sm text-decoration-none fw-semibold p-0"
                           style="font-size:.82rem; color:var(--app-primary); display:none;">Ver todos</a>
                    @endcan
                </div>
            </div>

            <div class="card-body pt-3">

                {{-- ── Cobros recientes ── --}}
                <div id="tab-cobros" class="tab-pane">
                    <div class="vstack gap-2">
                        @forelse ($recentPayments as $payment)
                            <div class="loan-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="loan-avatar"
                                         style="background:var(--app-primary-light); color:var(--app-primary);">
                                        {{ strtoupper(substr($payment->client?->full_name ?? '??', 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.88rem; color:var(--app-text);">
                                            {{ $payment->client?->full_name ?? 'Cliente eliminado' }}
                                        </div>
                                        <div class="text-muted" style="font-size:.75rem;">
                                            {{ $payment->payment_date->isoFormat('DD MMM YYYY') }}
                                            @if($payment->collector) · {{ $payment->collector->name }} @endif
                                            @if($payment->receipt_number)
                                                · <span style="font-family:monospace;">{{ $payment->receipt_number }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold" style="font-size:.9rem; color:var(--app-primary);">
                                        {{ currency() }} {{ number_format((float)$payment->amount, 2) }}
                                    </div>
                                    <span class="status-pill status-pill-active">Cobro</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-5">
                                <i class="fa-solid fa-receipt fa-2x mb-3 d-block opacity-40"></i>
                                No hay cobros registrados todavía.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- ── Últimos préstamos ── --}}
                <div id="tab-prestamos" class="tab-pane" style="display:none;">
                    <div class="vstack gap-2">
                        @forelse ($recentLoans as $loan)
                            @php
                                $loanPill = match($loan->status) {
                                    'active' => ['Activo',  'status-pill-active'],
                                    'late'   => ['En mora', 'status-pill-overdue'],
                                    'paid'   => ['Saldado', 'status-pill-paid'],
                                    default  => [ucfirst($loan->status), 'status-pill-paid'],
                                };
                            @endphp
                            <div class="loan-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="loan-avatar"
                                         style="background:var(--app-primary-light); color:var(--app-primary);">
                                        {{ strtoupper(substr($loan->client?->full_name ?? '??', 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.88rem; color:var(--app-text);">
                                            {{ $loan->client?->full_name ?? 'Cliente eliminado' }}
                                        </div>
                                        <div class="text-muted" style="font-size:.75rem;">
                                            {{ $loan->loan_number }}
                                            @if($loan->collector) · {{ $loan->collector->name }} @endif
                                            · {{ $loan->created_at->isoFormat('DD MMM YYYY') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold" style="font-size:.9rem; color:var(--app-primary);">
                                        {{ currency() }} {{ number_format((float)$loan->amount, 2) }}
                                    </div>
                                    <span class="status-pill {{ $loanPill[1] }}">{{ $loanPill[0] }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-5">
                                <i class="fa-solid fa-file-invoice-dollar fa-2x mb-3 d-block opacity-40"></i>
                                No hay préstamos registrados todavía.
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </article>
    </div>

    <div class="col-12 col-xl-4">
        <article class="card content-card h-100 anim-fade-up" style="animation-delay:240ms;">
            <div class="card-header bg-white border-0 pb-0 pt-3 px-4">
                <h3 class="h6 fw-bold mb-1" style="color:var(--app-primary);">Estado de inversión</h3>
                <p class="text-muted small mb-0">Indicadores de capital y rendimiento.</p>
            </div>
            <div class="card-body">
                <div class="invest-row">
                    <div class="d-flex align-items-center gap-3">
                        <span class="invest-icon" style="background:rgba(0,38,83,.1); color:var(--app-primary);">
                            <i class="fa-solid fa-vault"></i>
                        </span>
                        <span class="text-muted" style="font-size:.88rem;">Capital invertido</span>
                    </div>
                    <strong style="font-size:.88rem;">{{ currency() }} {{ number_format((float)$metrics['capital_invertido'], 2) }}</strong>
                </div>
                <div class="invest-row">
                    <div class="d-flex align-items-center gap-3">
                        <span class="invest-icon" style="background:rgba(0,71,15,.1); color:var(--app-success);">
                            <i class="fa-solid fa-wallet"></i>
                        </span>
                        <span class="text-muted" style="font-size:.88rem;">Capital disponible</span>
                    </div>
                    <strong style="font-size:.88rem; color:{{ (float) $metrics['capital_disponible'] < 0 ? 'var(--bs-danger, #dc3545)' : 'inherit' }};">{{ currency() }} {{ number_format((float)$metrics['capital_disponible'], 2) }}</strong>
                </div>
                <div class="invest-row">
                    <div class="d-flex align-items-center gap-3">
                        <span class="invest-icon" style="background:rgba(38,70,121,.1); color:var(--app-primary-tint);">
                            <i class="fa-solid fa-percent"></i>
                        </span>
                        <span class="text-muted" style="font-size:.88rem;">Intereses generados</span>
                    </div>
                    <strong style="font-size:.88rem;">{{ currency() }} {{ number_format((float)$metrics['intereses_generados'], 2) }}</strong>
                </div>
                <div class="invest-row">
                    <div class="d-flex align-items-center gap-3">
                        <span class="invest-icon" style="background:rgba(0,0,0,.06); color:var(--app-muted);">
                            <i class="fa-solid fa-circle-check"></i>
                        </span>
                        <span class="text-muted" style="font-size:.88rem;">Préstamos saldados</span>
                    </div>
                    <strong style="font-size:.88rem;">{{ number_format((int)$metrics['prestamos_saldados']) }}</strong>
                </div>

                @if(!empty($metrics['clientes_atrasados']) && $metrics['clientes_atrasados'] > 0)
                <div class="mt-3 p-3 rounded-3" style="background:var(--app-error-bg);">
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="font-size:.82rem; color:var(--app-error); font-weight:600;">
                            <i class="fa-solid fa-user-clock me-2"></i> Clientes atrasados
                        </span>
                        <strong style="color:var(--app-error); font-size:.9rem;">{{ number_format((int)$metrics['clientes_atrasados']) }}</strong>
                    </div>
                </div>
                @endif
            </div>
        </article>
    </div>
</section>

@endsection

@push('scripts')
<script>
/* ── Dashboard tabs ── */
(function () {
    const tabs   = document.querySelectorAll('.dash-tab');
    const panes  = document.querySelectorAll('.tab-pane');
    const linkCobros    = document.getElementById('dash-link-cobros');
    const linkPrestamos = document.getElementById('dash-link-prestamos');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((t) => t.classList.remove('active'));
            panes.forEach((p) => { p.style.display = 'none'; });
            tab.classList.add('active');
            const target = tab.dataset.tab;
            const pane = document.getElementById('tab-' + target);
            if (pane) pane.style.display = '';
            if (linkCobros)    linkCobros.style.display    = target === 'cobros'    ? '' : 'none';
            if (linkPrestamos) linkPrestamos.style.display = target === 'prestamos' ? '' : 'none';
        });
    });
})();

(function () {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (typeof Chart === 'undefined') return;
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = '#43474f';

    const trendEl = document.getElementById('collectionsChart');
    if (trendEl) {
        const ctx = trendEl.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, 'rgba(0,38,83,.25)');
        gradient.addColorStop(1, 'rgba(0,38,83,0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($collectionsTrend['labels']),
                datasets: [{
                    label: 'Cobrado',
                    data: @json($collectionsTrend['values']),
                    borderColor: '#002653',
                    backgroundColor: gradient,
                    borderWidth: 2.5,
                    fill: true,
                    tension: .4,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#002653',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: reduceMotion ? false : { duration: 800 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: { label: (c) => @json(currency().' ') + c.parsed.y.toLocaleString('es-DO', { minimumFractionDigits: 2 }) },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,.04)' },
                        ticks: { callback: (v) => @json(currency().' ') + v.toLocaleString('es-DO') },
                    },
                    x: { grid: { display: false } },
                },
            },
        });
    }

    const donutEl = document.getElementById('loanDistributionChart');
    if (donutEl) {
        const dist = @json($loanDistribution);
        new Chart(donutEl, {
            type: 'doughnut',
            data: {
                labels: ['Activos', 'En mora', 'Saldados'],
                datasets: [{
                    data: [dist.active, dist.late, dist.paid],
                    backgroundColor: ['#166534', '#ba1a1a', '#c4c6d0'],
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                animation: reduceMotion ? false : { animateRotate: true, duration: 800 },
                plugins: { legend: { display: false } },
            },
        });
    }
})();
</script>
@endpush

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Font Awesome --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    {{-- Inter font --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- Material Symbols --}}
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    {{-- NProgress --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.css">

    <style>
        /* ═══════════════════════════════════════════════
           Design tokens — Material Design 3 palette
        ═══════════════════════════════════════════════ */
        :root {
            /* Backgrounds */
            --app-bg:              #f7f9fc;
            --app-surface:         #ffffff;
            --app-surface-low:     #f2f4f7;
            --app-surface-mid:     #eceef1;
            --app-surface-high:    #e6e8eb;
            --app-surface-highest: #e0e3e6;

            /* Primary brand (deep navy) */
            --app-primary:         #002653;
            --app-primary-tint:    #1a3c6e;
            --app-primary-light:   #d7e3ff;
            --app-on-primary:      #ffffff;

            /* Secondary / CTA (amber-gold) */
            --app-secondary:       #feae2c;
            --app-on-secondary:    #6b4500;

            /* Status */
            --app-success:         #00470f;
            --app-success-bg:      #98f994;
            --app-error:           #ba1a1a;
            --app-error-bg:        #ffdad6;
            --app-warning:         #835500;
            --app-warning-bg:      #ffddb4;

            /* Text & borders */
            --app-text:            #191c1e;
            --app-muted:           #43474f;
            --app-border:          #c4c6d0;
            --app-border-light:    #e6e8eb;

            /* Sidebar */
            --app-sidebar:         #ffffff;
            --app-sidebar-width:   268px;
        }

        /* ═══════════════════════════════════════════════
           Base
        ═══════════════════════════════════════════════ */
        body {
            min-height: 100vh;
            background: var(--app-bg);
            color: var(--app-text);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: .9375rem;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }

        /* ═══════════════════════════════════════════════
           App shell
        ═══════════════════════════════════════════════ */
        .app-shell {
            display: grid;
            grid-template-columns: var(--app-sidebar-width) minmax(0, 1fr);
            min-height: 100vh;
        }

        /* ═══════════════════════════════════════════════
           Sidebar — WHITE with navy accents
        ═══════════════════════════════════════════════ */
        .sidebar {
            background: var(--app-sidebar);
            border-right: 1px solid var(--app-border);
            padding: 20px 12px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--app-border-light) transparent;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 10px 20px;
            font-weight: 700;
            color: var(--app-primary);
            text-decoration: none;
            letter-spacing: -0.01em;
        }

        .sidebar-brand-icon {
            width: 36px;
            height: 36px;
            display: inline-grid;
            place-items: center;
            border-radius: 10px;
            background: var(--app-primary);
            color: #fff;
            flex-shrink: 0;
            font-size: .9rem;
        }

        .sidebar-section {
            margin: 20px 10px 6px;
            color: var(--app-muted);
            font-size: .68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 40px;
            border-radius: 10px;
            padding: 6px 12px;
            color: var(--app-muted);
            font-size: .875rem;
            font-weight: 450;
            text-decoration: none;
            transition: background .15s ease, color .15s ease, padding-left .15s ease;
            position: relative;
        }

        .nav-link i {
            width: 18px;
            text-align: center;
            font-size: .85rem;
            opacity: .75;
            transition: opacity .15s ease;
        }

        .nav-link:hover {
            background: rgba(0, 38, 83, .06);
            color: var(--app-primary);
        }

        .nav-link:hover i {
            opacity: 1;
        }

        .nav-link.active {
            background: rgba(0, 38, 83, .1);
            color: var(--app-primary);
            font-weight: 600;
            padding-left: 18px;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0; top: 20%; bottom: 20%;
            width: 3px; border-radius: 0 3px 3px 0;
            background: var(--app-primary);
        }

        .nav-link.active i {
            opacity: 1;
        }

        /* ═══════════════════════════════════════════════
           Main content area
        ═══════════════════════════════════════════════ */
        .main {
            min-width: 0;
            padding: 24px;
        }

        /* ═══════════════════════════════════════════════
           Top bar — clean white
        ═══════════════════════════════════════════════ */
        .topbar {
            position: relative;
            z-index: 1040;
            min-height: 64px;
            border: 1px solid var(--app-border-light);
            border-radius: 14px;
            background: var(--app-surface);
            padding: 10px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
            margin-bottom: 1.5rem;
        }

        /* ═══════════════════════════════════════════════
           Metric cards
        ═══════════════════════════════════════════════ */
        .metric-card {
            border: 1px solid var(--app-border-light) !important;
            border-radius: 16px !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04) !important;
            overflow: hidden;
            position: relative;
            transition: transform .2s ease, box-shadow .2s ease;
            background: var(--app-surface);
        }

        .metric-card::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 3px;
            background: var(--app-primary);
            opacity: .6;
            border-radius: 4px 0 0 4px;
        }

        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 28px rgba(0, 38, 83, .1) !important;
        }

        .metric-icon {
            width: 46px;
            height: 46px;
            display: inline-grid;
            place-items: center;
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
            transition: transform .2s ease;
        }

        .metric-card:hover .metric-icon {
            transform: scale(1.06) rotate(-3deg);
        }

        .metric-value { font-variant-numeric: tabular-nums; }

        .metric-icon.bg-primary   { background: linear-gradient(135deg, #002653, #1a3c6e) !important; }
        .metric-icon.bg-success   { background: linear-gradient(135deg, #00470f, #006e1c) !important; }
        .metric-icon.bg-info      { background: linear-gradient(135deg, #264679, #405e92) !important; }
        .metric-icon.bg-danger    { background: linear-gradient(135deg, #ba1a1a, #c0392b) !important; }
        .metric-icon.bg-warning   { background: linear-gradient(135deg, #835500, #b07800) !important; }
        .metric-icon.bg-dark      { background: linear-gradient(135deg, #2d3133, #43474f) !important; }
        .metric-icon.bg-secondary { background: linear-gradient(135deg, #43474f, #6b7280) !important; }

        /* ═══════════════════════════════════════════════
           Content cards
        ═══════════════════════════════════════════════ */
        .content-card {
            border: 1px solid var(--app-border-light) !important;
            border-radius: 16px !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04) !important;
            background: var(--app-surface);
            transition: box-shadow .2s ease;
        }

        .content-card:hover {
            box-shadow: 0 6px 20px rgba(0, 38, 83, .07) !important;
        }

        /* ═══════════════════════════════════════════════
           Entrance animation
        ═══════════════════════════════════════════════ */
        @keyframes app-fade-up {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .anim-fade-up {
            opacity: 0;
            animation: app-fade-up .45s ease forwards;
        }

        @media (prefers-reduced-motion: reduce) {
            .anim-fade-up { animation: none; opacity: 1; }
            .metric-card, .metric-icon { transition: none; }
        }

        /* ═══════════════════════════════════════════════
           NProgress bar — amber gold theme
        ═══════════════════════════════════════════════ */
        #nprogress .bar { background: var(--app-secondary) !important; height: 3px !important; }
        #nprogress .peg { box-shadow: 0 0 8px var(--app-secondary), 0 0 4px var(--app-secondary) !important; }

        /* ═══════════════════════════════════════════════
           App flash alerts — multi-type + animated
        ═══════════════════════════════════════════════ */
        @keyframes alert-enter {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .app-alert {
            animation: alert-enter .3s ease forwards;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 1rem;
            display: flex; align-items: center; gap: 12px;
            font-size: .875rem; font-weight: 500;
            border: 1px solid transparent;
        }
        .app-alert .alert-close {
            margin-left: auto; background: none; border: none; cursor: pointer;
            opacity: .5; font-size: .9rem; padding: 0; line-height: 1;
            transition: opacity .15s ease;
        }
        .app-alert .alert-close:hover { opacity: 1; }
        .app-alert-success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
        .app-alert-error   { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        .app-alert-warning { background: #fffbeb; border-color: #fde68a; color: #92400e; }
        .app-alert-info    { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
        .app-alert-status  { background: var(--app-surface-low); border-color: var(--app-border); color: var(--app-text); }

        /* ═══════════════════════════════════════════════
           Utilities
        ═══════════════════════════════════════════════ */
        .text-muted { color: var(--app-muted) !important; }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        /* ═══════════════════════════════════════════════
           Alerts / notifications dropdown
        ═══════════════════════════════════════════════ */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 38, 83, .35);
            z-index: 1045;
            opacity: 0;
            transition: opacity .25s ease;
        }

        .sidebar-toggle {
            display: none;
        }

        .operation-alerts-menu {
            z-index: 1080;
            width: min(400px, calc(100vw - 32px));
            max-height: calc(100vh - 120px);
            overflow-y: auto;
            overscroll-behavior: contain;
            box-shadow: 0 16px 40px rgba(0, 38, 83, .14);
            border: 1px solid var(--app-border-light);
            border-radius: 14px !important;
        }

        .topbar .dropdown { position: relative; z-index: 1080; }
        .operation-alerts-menu .dropdown-item { white-space: normal; }
        .operation-alerts-menu .alert-label   { min-width: 0; overflow-wrap: anywhere; line-height: 1.3; }
        .operation-alerts-menu .alert-count   { flex: 0 0 auto; min-width: 2rem; text-align: right; }

        /* ═══════════════════════════════════════════════
           Responsive
        ═══════════════════════════════════════════════ */
        @media (max-width: 991.98px) {
            .app-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                top: 0; left: 0;
                width: var(--app-sidebar-width);
                height: 100vh;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform .28s ease;
                box-shadow: 4px 0 24px rgba(0, 0, 0, .12);
            }

            .sidebar.open { transform: translateX(0); }
            .sidebar-backdrop.show { display: block; opacity: 1; }

            .sidebar-toggle {
                display: inline-grid;
                place-items: center;
                width: 40px;
                height: 40px;
                border: 1px solid var(--app-border);
                border-radius: 10px;
                background: var(--app-surface);
                color: var(--app-text);
            }

            .main { padding: 16px; }
        }

        @media (max-width: 575.98px) {
            .topbar { gap: 10px; }
            .topbar > .d-flex:first-child  { flex: 1 1 auto; min-width: 0; }
            .topbar > .d-flex:last-child   { flex: 0 0 auto; flex-wrap: wrap; justify-content: flex-end; row-gap: 8px; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="app-shell">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    {{-- ── Sidebar ── --}}
    <aside class="sidebar" id="appSidebar">
        <a class="sidebar-brand" href="{{ route('dashboard') }}">
            <span class="sidebar-brand-icon"><i class="fa-solid fa-hand-holding-dollar"></i></span>
            <span>{{ config('app.name') }}</span>
        </a>

        <nav class="nav flex-column gap-1">
            @foreach ($navigationSections as $section)
                <div class="sidebar-section">{{ $section['label'] }}</div>
                @foreach ($section['items'] as $item)
                    @can($item['permission'])
                        @php
                            $routeBase = preg_replace('/\.index$/', '', $item['route']);
                            $isActive  = request()->routeIs($routeBase) || request()->routeIs($routeBase . '.*');
                        @endphp
                        <a class="nav-link {{ $isActive ? 'active' : '' }}"
                           href="{{ route($item['route']) }}">
                            <i class="fa-solid {{ $item['icon'] }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endcan
                @endforeach
            @endforeach
        </nav>
    </aside>

    {{-- ── Main ── --}}
    <main class="main">
        <header class="topbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="sidebar-toggle" id="sidebarToggle"
                        aria-label="Abrir menú" aria-controls="appSidebar" aria-expanded="false">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <div class="text-muted" style="font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em;">Empresa</div>
                    <div class="fw-semibold" style="font-size:.93rem; color:var(--app-primary);">{{ auth()->user()->company->name }}</div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                @php
                    $canViewRouteMap  = auth()->user()->can('routes.manage');
                    $canViewPayments  = auth()->user()->can('payments.create');
                    $alertCount       = ($canViewRouteMap  ? (int)($operationAlerts['missing_coordinates'] ?? 0) : 0)
                                      + ($canViewPayments  ? (int)($operationAlerts['late_installments']   ?? 0) : 0);
                    $unreadCount      = (int)($unreadNotificationsCount ?? 0);
                    $topbarCount      = $unreadCount + $alertCount;
                @endphp

                <div class="dropdown">
                    <button class="btn btn-outline-secondary position-relative" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false" title="Notificaciones"
                            style="border-color:var(--app-border); border-radius:10px; width:40px; height:40px; padding:0; display:inline-grid; place-items:center;">
                        <i class="fa-solid fa-bell" style="color:var(--app-muted);"></i>
                        @if ($topbarCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                  style="font-size:.6rem;">{{ $topbarCount > 99 ? '99+' : $topbarCount }}</span>
                        @endif
                    </button>

                    <div class="dropdown-menu dropdown-menu-end p-0 operation-alerts-menu">
                        <div class="px-3 py-2 border-bottom fw-semibold d-flex justify-content-between align-items-center"
                             style="font-size:.85rem; color:var(--app-primary);">
                            <span>Notificaciones</span>
                            @if ($unreadCount > 0)
                                <form method="POST" action="{{ route('notifications.read-all') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none"
                                            style="color:var(--app-primary); font-size:.75rem;">Marcar todas</button>
                                </form>
                            @endif
                        </div>

                        @forelse ($unreadNotifications ?? [] as $notification)
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item py-3 text-start w-100 border-0 bg-transparent">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <span class="alert-label" style="font-size:.85rem;">
                                            <i class="fa-solid {{ $notification->data['icon'] ?? 'fa-bell' }} me-2"
                                               style="color:var(--app-primary);"></i>
                                            <strong>{{ $notification->data['title'] ?? 'Notificación' }}</strong><br>
                                            <span class="text-muted small">{{ $notification->data['message'] ?? '' }}</span>
                                        </span>
                                        <span class="small text-muted text-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                                    </div>
                                </button>
                            </form>
                        @empty
                            <div class="px-3 py-3 text-muted small">No tienes notificaciones nuevas.</div>
                        @endforelse

                        <div class="border-top">
                            <div class="px-3 py-2 border-bottom fw-semibold"
                                 style="font-size:.8rem; color:var(--app-muted); text-transform:uppercase; letter-spacing:.05em;">Alertas operativas</div>
                            @if ($canViewRouteMap)
                                <a class="dropdown-item py-3" href="{{ route('routes.map') }}" style="font-size:.85rem;">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <span class="alert-label">
                                            <i class="fa-solid fa-map-location-dot me-2" style="color:var(--app-primary);"></i>
                                            Clientes sin coordenadas
                                        </span>
                                        <strong class="alert-count">{{ (int)($operationAlerts['missing_coordinates'] ?? 0) }}</strong>
                                    </div>
                                </a>
                            @endif
                            @if ($canViewPayments)
                                <a class="dropdown-item py-3" href="{{ route('payments.index') }}" style="font-size:.85rem;">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <span class="alert-label">
                                            <i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>
                                            Cuotas en mora
                                        </span>
                                        <strong class="alert-count">{{ (int)($operationAlerts['late_installments'] ?? 0) }}</strong>
                                    </div>
                                </a>
                            @endif
                            @if (!$canViewRouteMap && !$canViewPayments)
                                <div class="px-3 py-3 text-muted small">No tienes alertas disponibles para tu rol.</div>
                            @endif
                        </div>
                        <a class="dropdown-item text-center py-2 border-top" href="{{ route('notifications.index') }}"
                           style="font-size:.82rem; color:var(--app-primary); font-weight:600;">Ver todas</a>
                    </div>
                </div>

                <div class="text-end d-none d-sm-block">
                    <div class="fw-semibold" style="font-size:.88rem;">{{ auth()->user()->name }}</div>
                    <div class="text-muted" style="font-size:.75rem;">{{ auth()->user()->email }}</div>
                </div>

                <form method="POST" action="{{ route('logout') }}" data-no-loading>
                    @csrf
                    <button type="submit" class="btn btn-link text-decoration-none px-0"
                            style="color:var(--app-error); font-size:.85rem;" title="Cerrar sesión">
                        <i class="fa-solid fa-right-from-bracket me-1"></i> Salir
                    </button>
                </form>
            </div>
        </header>

        @php
            $flashMessages = [
                'success' => ['app-alert-success', 'fa-circle-check'],
                'error'   => ['app-alert-error',   'fa-circle-xmark'],
                'warning' => ['app-alert-warning',  'fa-triangle-exclamation'],
                'info'    => ['app-alert-info',     'fa-circle-info'],
                'status'  => ['app-alert-status',   'fa-circle-info'],
            ];
        @endphp
        @foreach ($flashMessages as $key => [$cls, $icon])
            @if (session($key))
                <div class="app-alert {{ $cls }}" role="alert" data-auto-dismiss>
                    <i class="fa-solid {{ $icon }}"></i>
                    <span>{{ session($key) }}</span>
                    <button type="button" class="alert-close" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif
        @endforeach

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.js"></script>
<script>
/* ── NProgress page loading ── */
NProgress.configure({ showSpinner: false, speed: 220, minimum: 0.15, trickleSpeed: 60 });
document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a) return;
    const href = a.getAttribute('href') || '';
    if (!href || href.startsWith('#') || href.startsWith('javascript') ||
        a.target === '_blank' || a.hasAttribute('download')) return;
    try { if (new URL(a.href).origin !== location.origin) return; } catch (_) { return; }
    NProgress.start();
});
document.addEventListener('submit', () => NProgress.start());
NProgress.done();

/* ── Flash alert auto-dismiss + close button ── */
document.querySelectorAll('[data-auto-dismiss]').forEach((el) => {
    const dismiss = () => {
        el.style.transition = 'opacity .35s ease, transform .35s ease';
        el.style.opacity    = '0';
        el.style.transform  = 'translateY(-6px)';
        setTimeout(() => el.remove(), 360);
    };
    el.querySelector('.alert-close')?.addEventListener('click', dismiss);
    setTimeout(dismiss, 4500);
});

/* ── Form submit — loading state ── */
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.dataset.noLoading !== undefined) return;
    const btn = form.querySelector('[type=submit]:not([data-no-loading]):not([disabled])');
    if (!btn) return;
    btn._origText = btn.innerHTML;
    btn.disabled  = true;
    btn.style.opacity = '.7';
    const spinner = '<span class="spinner-border spinner-border-sm me-2" style="width:13px;height:13px;border-width:2px;vertical-align:middle;" aria-hidden="true"></span>';
    btn.innerHTML = spinner + btn._origText;
    setTimeout(() => {
        btn.disabled     = false;
        btn.style.opacity = '';
        btn.innerHTML    = btn._origText;
    }, 10000);
});

(function () {
    const sidebar  = document.getElementById('appSidebar');
    const toggle   = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar || !toggle || !backdrop) return;

    const open  = () => { sidebar.classList.add('open');    backdrop.classList.add('show');    toggle.setAttribute('aria-expanded', 'true');  };
    const close = () => { sidebar.classList.remove('open'); backdrop.classList.remove('show'); toggle.setAttribute('aria-expanded', 'false'); };

    toggle.addEventListener('click',   () => sidebar.classList.contains('open') ? close() : open());
    backdrop.addEventListener('click', close);
    sidebar.querySelectorAll('.nav-link').forEach((l) => l.addEventListener('click', close));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
})();
</script>
@stack('scripts')
</body>
</html>

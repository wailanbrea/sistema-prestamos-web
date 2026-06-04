<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --app-bg: #f5f7fb;
            --app-sidebar: #111827;
            --app-sidebar-muted: #9ca3af;
            --app-primary: #5e72e4;
            --app-text: #172b4d;
            --app-muted: #67748e;
            --app-border: #e9ecef;
        }

        body {
            min-height: 100vh;
            background: var(--app-bg);
            color: var(--app-text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .app-shell {
            display: grid;
            grid-template-columns: 268px minmax(0, 1fr);
            min-height: 100vh;
        }

        .sidebar {
            background: var(--app-sidebar);
            color: #fff;
            padding: 20px 16px;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px 20px;
            font-weight: 700;
            letter-spacing: 0;
        }

        .sidebar-brand-icon {
            width: 38px;
            height: 38px;
            display: inline-grid;
            place-items: center;
            border-radius: 8px;
            background: var(--app-primary);
        }

        .sidebar-section {
            margin: 18px 10px 8px;
            color: var(--app-sidebar-muted);
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 42px;
            border-radius: 8px;
            color: #d1d5db;
            font-size: .93rem;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, .08);
            color: #fff;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .main {
            min-width: 0;
            padding: 20px;
        }

        .topbar {
            position: relative;
            z-index: 1040;
            min-height: 68px;
            border: 1px solid var(--app-border);
            border-radius: 8px;
            background: rgba(255, 255, 255, .9);
            backdrop-filter: blur(12px);
            padding: 12px 18px;
            box-shadow: 0 4px 18px rgba(50, 50, 93, .05);
        }

        .metric-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(50, 50, 93, .08);
            overflow: hidden;
            position: relative;
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .metric-card::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: var(--app-primary);
            opacity: .85;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 38px rgba(50, 50, 93, .16);
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            display: inline-grid;
            place-items: center;
            border-radius: 12px;
            color: #fff;
            font-size: 1.1rem;
            box-shadow: 0 6px 16px rgba(50, 50, 93, .18);
            transition: transform .25s ease;
        }

        .metric-card:hover .metric-icon {
            transform: scale(1.08) rotate(-4deg);
        }

        .metric-value {
            font-variant-numeric: tabular-nums;
        }

        /* Soft gradient backgrounds for metric icons */
        .metric-icon.bg-primary { background: linear-gradient(135deg, #5e72e4, #825ee4) !important; }
        .metric-icon.bg-success { background: linear-gradient(135deg, #2dce89, #2dcecc) !important; }
        .metric-icon.bg-info { background: linear-gradient(135deg, #11cdef, #1171ef) !important; }
        .metric-icon.bg-danger { background: linear-gradient(135deg, #f5365c, #f56036) !important; }
        .metric-icon.bg-warning { background: linear-gradient(135deg, #fb6340, #fbb140) !important; }
        .metric-icon.bg-dark { background: linear-gradient(135deg, #212529, #3a3f44) !important; }
        .metric-icon.bg-secondary { background: linear-gradient(135deg, #6c757d, #8a96a3) !important; }

        /* Entrance animation */
        @keyframes app-fade-up {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .anim-fade-up {
            opacity: 0;
            animation: app-fade-up .5s ease forwards;
        }

        @media (prefers-reduced-motion: reduce) {
            .anim-fade-up { animation: none; opacity: 1; }
            .metric-card, .metric-icon { transition: none; }
        }

        .text-muted {
            color: var(--app-muted) !important;
        }

        .content-card {
            border: 1px solid var(--app-border);
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(50, 50, 93, .05);
            transition: box-shadow .25s ease;
        }

        .content-card:hover {
            box-shadow: 0 14px 34px rgba(50, 50, 93, .1);
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, .5);
            z-index: 1045;
            opacity: 0;
            transition: opacity .25s ease;
        }

        .sidebar-toggle {
            display: none;
        }

        .operation-alerts-menu {
            z-index: 1080;
            width: min(420px, calc(100vw - 32px));
            max-width: calc(100vw - 32px);
            max-height: calc(100vh - 120px);
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: contain;
            box-shadow: 0 18px 45px rgba(17, 24, 39, .16);
        }

        .topbar .dropdown {
            position: relative;
            z-index: 1080;
        }

        .operation-alerts-menu .dropdown-item {
            white-space: normal;
        }

        .operation-alerts-menu .alert-label {
            min-width: 0;
            overflow-wrap: anywhere;
            line-height: 1.3;
        }

        .operation-alerts-menu .alert-count {
            flex: 0 0 auto;
            min-width: 2rem;
            text-align: right;
        }

        @media (max-width: 991.98px) {
            .app-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 268px;
                height: 100vh;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform .28s ease;
                overflow-y: auto;
                box-shadow: 0 0 40px rgba(0, 0, 0, .35);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-backdrop.show {
                display: block;
                opacity: 1;
            }

            .sidebar-toggle {
                display: inline-grid;
                place-items: center;
                width: 42px;
                height: 42px;
                border: 1px solid var(--app-border);
                border-radius: 10px;
                background: #fff;
                color: var(--app-text);
            }

            .main {
                padding: 16px;
            }
        }

        @media (max-width: 575.98px) {
            .topbar {
                gap: 10px;
            }

            /* Keep the hamburger + company block pinned to the left */
            .topbar > .d-flex:first-child {
                flex: 1 1 auto;
                min-width: 0;
            }

            /* Only the actions block wraps to the right */
            .topbar > .d-flex:last-child {
                flex: 0 0 auto;
                flex-wrap: wrap;
                justify-content: flex-end;
                row-gap: 8px;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="app-shell">
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <aside class="sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <span class="sidebar-brand-icon"><i class="fa-solid fa-hand-holding-dollar"></i></span>
                <span>{{ config('app.name') }}</span>
            </div>

            <nav class="nav flex-column gap-1">
                @foreach ($navigationSections as $section)
                    <div class="sidebar-section">{{ $section['label'] }}</div>
                    @foreach ($section['items'] as $item)
                        @can($item['permission'])
                            <a class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                                <i class="fa-solid {{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endcan
                    @endforeach
                @endforeach
            </nav>
        </aside>

        <main class="main">
            <header class="topbar d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir menú" aria-controls="appSidebar" aria-expanded="false">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <div class="text-muted small">Empresa</div>
                        <div class="fw-semibold">{{ auth()->user()->company->name }}</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    @php
                        $canViewRouteMap = auth()->user()->can('routes.manage');
                        $canViewPayments = auth()->user()->can('payments.create');
                        $alertCount = ($canViewRouteMap ? (int) ($operationAlerts['missing_coordinates'] ?? 0) : 0)
                            + ($canViewPayments ? (int) ($operationAlerts['late_installments'] ?? 0) : 0);
                        $unreadCount = (int) ($unreadNotificationsCount ?? 0);
                        $topbarNotificationCount = $unreadCount + $alertCount;
                    @endphp
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notificaciones">
                            <i class="fa-solid fa-bell"></i>
                            @if ($topbarNotificationCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $topbarNotificationCount > 99 ? '99+' : $topbarNotificationCount }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0 operation-alerts-menu">
                            <div class="px-3 py-2 border-bottom fw-semibold d-flex justify-content-between align-items-center">
                                <span>Notificaciones</span>
                                @if ($unreadCount > 0)
                                    <form method="POST" action="{{ route('notifications.read-all') }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">Marcar todas</button>
                                    </form>
                                @endif
                            </div>
                            @forelse ($unreadNotifications ?? [] as $notification)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-3 text-start w-100 border-0 bg-transparent">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <span class="alert-label">
                                                <i class="fa-solid {{ $notification->data['icon'] ?? 'fa-bell' }} me-2 text-primary"></i>
                                                <strong>{{ $notification->data['title'] ?? 'Notificación' }}</strong><br>
                                                <span class="small text-muted">{{ $notification->data['message'] ?? '' }}</span>
                                            </span>
                                            <span class="small text-muted text-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                                        </div>
                                    </button>
                                </form>
                            @empty
                                <div class="px-3 py-3 text-muted small">No tienes notificaciones nuevas.</div>
                            @endforelse
                            <div class="border-top">
                                <div class="px-3 py-2 border-bottom fw-semibold">Alertas operativas</div>
                                @if ($canViewRouteMap)
                                    <a class="dropdown-item py-3" href="{{ route('routes.map') }}">
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                            <span class="alert-label"><i class="fa-solid fa-map-location-dot me-2 text-primary"></i> Clientes sin coordenadas</span>
                                            <strong class="alert-count">{{ (int) ($operationAlerts['missing_coordinates'] ?? 0) }}</strong>
                                        </div>
                                    </a>
                                @endif
                                @if ($canViewPayments)
                                    <a class="dropdown-item py-3" href="{{ route('payments.index') }}">
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                            <span class="alert-label"><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i> Cuotas en mora</span>
                                            <strong class="alert-count">{{ (int) ($operationAlerts['late_installments'] ?? 0) }}</strong>
                                        </div>
                                    </a>
                                @endif
                                @if (! $canViewRouteMap && ! $canViewPayments)
                                    <div class="px-3 py-3 text-muted small">No tienes alertas disponibles para tu rol.</div>
                                @endif
                            </div>
                            <a class="dropdown-item text-center py-2 border-top" href="{{ route('notifications.index') }}">Ver todas</a>
                            @if ($unreadCount === 0 && $alertCount === 0)
                                <div class="px-3 pb-3 text-muted small text-center">No hay novedades pendientes.</div>
                            @endif
                        </div>
                    </div>
                    <div class="text-end d-none d-sm-block">
                        <div class="fw-semibold">{{ auth()->user()->name }}</div>
                        <div class="text-muted small">{{ auth()->user()->email }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-link text-danger text-decoration-none px-0" title="Cerrar sesión">
                            <i class="fa-solid fa-right-from-bracket me-1"></i> Salir
                        </button>
                    </form>
                </div>
            </header>

            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const sidebar = document.getElementById('appSidebar');
            const toggle = document.getElementById('sidebarToggle');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (!sidebar || !toggle || !backdrop) {
                return;
            }

            const open = () => {
                sidebar.classList.add('open');
                backdrop.classList.add('show');
                toggle.setAttribute('aria-expanded', 'true');
            };
            const close = () => {
                sidebar.classList.remove('open');
                backdrop.classList.remove('show');
                toggle.setAttribute('aria-expanded', 'false');
            };

            toggle.addEventListener('click', () => {
                sidebar.classList.contains('open') ? close() : open();
            });
            backdrop.addEventListener('click', close);
            sidebar.querySelectorAll('.nav-link').forEach((link) => link.addEventListener('click', close));
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    close();
                }
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>

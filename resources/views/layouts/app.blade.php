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
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(50, 50, 93, .08);
        }

        .metric-icon {
            width: 46px;
            height: 46px;
            display: inline-grid;
            place-items: center;
            border-radius: 8px;
            color: #fff;
        }

        .text-muted {
            color: var(--app-muted) !important;
        }

        .content-card {
            border: 1px solid var(--app-border);
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(50, 50, 93, .05);
        }

        @media (max-width: 991.98px) {
            .app-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: relative;
                height: auto;
            }

            .main {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
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
                <div>
                    <div class="text-muted small">Empresa</div>
                    <div class="fw-semibold">{{ auth()->user()->company->name }}</div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    @php
                        $alertCount = (int) ($operationAlerts['missing_coordinates'] ?? 0) + (int) ($operationAlerts['late_installments'] ?? 0);
                    @endphp
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Alertas operativas">
                            <i class="fa-solid fa-bell"></i>
                            @if ($alertCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $alertCount }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 320px;">
                            <div class="px-3 py-2 border-bottom fw-semibold">Alertas operativas</div>
                            <a class="dropdown-item py-3" href="{{ route('routes.map') }}">
                                <div class="d-flex justify-content-between gap-3">
                                    <span><i class="fa-solid fa-map-location-dot me-2 text-primary"></i> Clientes sin coordenadas</span>
                                    <strong>{{ (int) ($operationAlerts['missing_coordinates'] ?? 0) }}</strong>
                                </div>
                            </a>
                            <a class="dropdown-item py-3" href="{{ route('payments.index') }}">
                                <div class="d-flex justify-content-between gap-3">
                                    <span><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i> Cuotas en mora</span>
                                    <strong>{{ (int) ($operationAlerts['late_installments'] ?? 0) }}</strong>
                                </div>
                            </a>
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
</body>
</html>

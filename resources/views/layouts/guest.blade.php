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
            --prestamista-bg: #f5f7fb;
            --prestamista-primary: #5e72e4;
            --prestamista-dark: #172b4d;
            --prestamista-muted: #67748e;
        }

        body {
            background: radial-gradient(circle at top left, rgba(94, 114, 228, .18), transparent 36%), var(--prestamista-bg);
            color: var(--prestamista-dark);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            align-items: center;
            padding: 32px 16px;
        }

        .auth-card {
            width: min(100%, 440px);
            margin: 0 auto;
            border: 0;
            border-radius: 8px;
            box-shadow: 0 20px 45px rgba(50, 50, 93, .12), 0 8px 20px rgba(0, 0, 0, .08);
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            display: inline-grid;
            place-items: center;
            border-radius: 8px;
            background: var(--prestamista-primary);
            color: #fff;
        }

        .btn-primary {
            --bs-btn-bg: var(--prestamista-primary);
            --bs-btn-border-color: var(--prestamista-primary);
            --bs-btn-hover-bg: #4b5fc9;
            --bs-btn-hover-border-color: #4b5fc9;
        }
    </style>
</head>
<body>
    <main class="auth-shell">
        @yield('content')
    </main>
</body>
</html>

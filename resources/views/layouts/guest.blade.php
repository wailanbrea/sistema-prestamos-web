<!doctype html>
<html class="light" lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#002653",
                        "on-primary": "#ffffff",
                        "primary-container": "#1a3c6e",
                        "on-primary-container": "#8aa8e0",
                        "primary-fixed": "#d7e3ff",
                        "on-primary-fixed": "#001b3f",
                        "on-primary-fixed-variant": "#264679",
                        "secondary-container": "#feae2c",
                        "on-secondary-container": "#6b4500",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#f2f4f7",
                        "surface-container": "#eceef1",
                        "surface-container-high": "#e6e8eb",
                        "on-surface": "#191c1e",
                        "on-surface-variant": "#43474f",
                        "outline": "#747780",
                        "outline-variant": "#c4c6d0",
                        "error": "#ba1a1a",
                        "error-container": "#ffdad6",
                        "on-error-container": "#93000a",
                        "background": "#f7f9fc",
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
            min-height: max(884px, 100dvh);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        @keyframes slide-up {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up { animation: slide-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    </style>
</head>
<body class="bg-background min-h-screen flex flex-col items-center justify-between selection:bg-primary-fixed selection:text-on-primary-fixed">
    <div class="fixed top-0 left-0 w-full h-1/2 bg-gradient-to-b from-[#002653]/5 to-transparent -z-10 pointer-events-none"></div>
    @yield('content')
</body>
</html>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Servicio Terminado</title>
    <style>
        :root {
            color-scheme: dark;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            color: #f8fafc;
            background: #101827;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background: radial-gradient(circle at 20% 10%, #263c5c, transparent 42%), #101827;
        }

        main {
            width: min(560px, 100%);
            padding: clamp(32px, 8vw, 64px);
            text-align: center;
            border: 1px solid rgba(148, 163, 184, .24);
            border-radius: 24px;
            background: rgba(15, 23, 42, .82);
            box-shadow: 0 24px 80px rgba(0, 0, 0, .3);
        }

        .status {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            color: #fed7aa;
            background: rgba(194, 65, 12, .28);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 24px 0 12px;
            font-size: clamp(32px, 8vw, 52px);
            line-height: 1;
        }

        p {
            margin: 0;
            color: #cbd5e1;
            font-size: 17px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <main>
        <span class="status">Acceso cerrado</span>
        <h1>Servicio Terminado</h1>
        <p>El acceso al sistema ha finalizado y ya no se encuentra disponible.</p>
    </main>
</body>
</html>

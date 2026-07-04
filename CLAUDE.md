# Sistema de Préstamos (Laravel)

App Laravel de gestión de préstamos (multi-empresa, roles admin/cobrador/supervisor/caja) con API v2 para la app Android companion. Base de datos MySQL en local y en el VPS (los tests usan SQLite).

## Ejecutar y depurar en el navegador

- **URL local: http://localhost:8000** — servir con `php artisan serve --port=8000` en segundo plano (verificar antes si ya está corriendo).
- ⚠️ `https://localhost` / `http://localhost` (Apache XAMPP, puertos 80/443) sirven **otro proyecto (BSLotery)** por vhost — NO es esta app.
- Usuario de prueba (seeder): `admin@sistemaprestamista.local` / `Password123!`.
- Para verificar cambios visualmente o depurar flujos, usar el navegador real vía Playwright MCP (`mcp__playwright__*`): el flujo completo está en la skill de usuario `depurar-web`. No dar por terminado un cambio de UI/flujo sin verlo funcionar en el navegador.
- Errores de servidor: `storage/logs/laravel.log`.

## Comandos útiles

- Tests: `php artisan test` (SQLite en memoria; cuidado con SQL específico de MySQL).
- Migraciones: `php artisan migrate`.
- Seed demo: `php artisan db:seed` (crea el admin de prueba).

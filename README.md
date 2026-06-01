# Sistema de Préstamos

Sistema de gestión de préstamos, cobros, rutas y reportes para prestamistas
(República Dominicana). Construido con **Laravel 12** + **MySQL 8** y una UI
basada en Blade, Bootstrap 5 y Chart.js.

---

## Requisitos

- PHP 8.2+ con las extensiones `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`.
- **MySQL 8+** (o MariaDB de XAMPP) corriendo en `127.0.0.1:3306`.
- [Composer](https://getcomposer.org/)
- [Node.js 18+](https://nodejs.org/) y npm
- Conexión a internet en el navegador (Bootstrap, Font Awesome y Chart.js se
  cargan por CDN; sin internet el dashboard no dibuja los gráficos).

---

## Instalación tras clonar

> ⚠️ **La base de datos NO está en el repositorio.** Por defecto el `.env` apunta
> a MySQL (`sistema_prestamos`), pero esa base no existe al clonar, así que llegas
> **sin datos**: sin empresa, sin plan/licencia y sin préstamos. Por eso, recién
> clonado, no ves la configuración de licencias ni datos en los gráficos del
> dashboard. Hay que **crear la base y poblarla** antes de usar el sistema.

Hay dos formas de poblar la base:

| Forma | Comando | Qué obtienes |
|-------|---------|--------------|
| **Snapshot** (recomendado) | `./setup.ps1` | **Todos los datos actuales**: empresa con plan `prestamista`, ~22 préstamos, 14 pagos, clientes, rutas. Dashboard y gráficos completos. |
| **Seeders** | `./setup.ps1 -Seed` | Portafolio de **demo reducido** (3 préstamos) generado por los seeders. |

El snapshot versionado es un dump MySQL en `docs/data/seed-snapshot.sql` que
`setup.ps1` importa en la base `sistema_prestamos`.

### Opción rápida (Windows / PowerShell)

```powershell
./setup.ps1          # crea la base e importa el snapshot con todos los datos
# ./setup.ps1 -Seed  # o construye un demo reducido con los seeders
```

El script verifica herramientas, instala dependencias, prepara `.env`, genera la
app key, crea la base MySQL, la puebla (snapshot o seeders) y compila los assets.
Acepta `-DbUser`, `-DbPassword`, `-Database` y `-MysqlBin` si tu MySQL no usa los
valores por defecto de XAMPP (`root` sin contraseña).

### Pasos manuales

```bash
composer install
npm install

# Configuración
cp .env.example .env          # PowerShell: Copy-Item .env.example .env
php artisan key:generate

# Crear la base (ajusta credenciales según tu MySQL)
mysql -u root -e "CREATE DATABASE sistema_prestamos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Base de datos — opción A: importar el snapshot (datos completos)
mysql -u root sistema_prestamos < docs/data/seed-snapshot.sql
#   PowerShell: Get-Content docs/data/seed-snapshot.sql -Raw | mysql -u root sistema_prestamos

# Base de datos — opción B: migrar y sembrar (demo reducido)
# php artisan migrate --seed

# Assets
npm run build                 # o: npm run dev (modo desarrollo con HMR)
```

Luego levanta la app:

```bash
php artisan serve
```

y abre http://127.0.0.1:8000

---

## Datos de demo (seeders)

`php artisan migrate --seed` ejecuta:

- **`RolePermissionSeeder`** — roles (Administrador, Cobrador, …) y permisos.
- **`DemoLoanPortfolioSeeder`** — empresa de demo con su configuración actual:
  - Plan/licencia: **`prestamista`** (básico)
  - Moneda `RD$`, tasa por defecto 10 %, mora diaria fija RD$75
  - Radio de visita de ruta: 75 m
  - Cobrador, zona, ruta, 3 clientes y 3 préstamos (mensual, semanal y diario
    atrasado) con un pago registrado, para poblar dashboard, gráficos y mora.

### Credenciales de acceso

| Rol           | Email                                  | Contraseña     |
|---------------|----------------------------------------|----------------|
| Administrador | `admin@sistemaprestamista.local`       | `Password123!` |
| Cobrador      | `cobrador@sistemaprestamista.local`    | `Password123!` |

> Para **re-sembrar desde cero**: `php artisan migrate:fresh --seed`.

### Actualizar el snapshot

Cuando cambies datos y quieras versionarlos para los demás, regenera el dump:

```powershell
C:\xampp\mysql\bin\mysqldump.exe -u root --no-tablespaces --add-drop-table `
  --default-character-set=utf8mb4 sistema_prestamos > docs/data/seed-snapshot.sql
```

---

## Solución de problemas

- **No veo la configuración de licencias / no hay datos.** No poblaste la base
  tras clonar. Ejecuta `./setup.ps1` (importa el snapshot con todos los datos)
  o `./setup.ps1 -Seed` (demo reducido con seeders).
- **El dashboard no muestra gráficos.** Dos causas posibles: (1) no hay datos
  → los gráficos salen vacíos; (2) el equipo no tiene internet y Chart.js (CDN)
  no carga. Verifica ambos.
- **`SQLSTATE[HY000] [2002]` / no conecta a la base.** MySQL no está corriendo o
  las credenciales del `.env` no coinciden. Arranca MySQL en XAMPP y revisa
  `DB_USERNAME` / `DB_PASSWORD` / `DB_DATABASE`.
- **Estilos rotos / sin JS.** Falta compilar los assets: `npm run build`.

---

## Despliegue

Ver [`docs/DEPLOY-WINDOWS-VPS.md`](docs/DEPLOY-WINDOWS-VPS.md) para el despliegue
en un VPS Windows.

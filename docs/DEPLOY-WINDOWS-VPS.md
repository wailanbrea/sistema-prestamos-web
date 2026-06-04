# Despliegue en VPS Windows — Sistema de Préstamos

Guía para desplegar el sistema en un VPS Windows (XAMPP / PHP 8.2). El repositorio
incluye un **dump MySQL con todos los datos actuales** en `docs/data/seed-snapshot.sql`
(empresa, usuarios, clientes, préstamos y pagos). La base de datos MySQL no está
versionada (buena práctica); en el despliegue se crea la base y se importa el dump,
dejando el sistema "llave en mano".

- **Repositorio:** https://github.com/wailanbrea/sistema-prestamos-web
- **Rama:** `main`
- **Stack:** Laravel 12 · PHP 8.2 · MySQL 8 · Bootstrap 5 (vía CDN, no requiere build de Vite)

---

## 1. Requisitos en el VPS

- **PHP 8.2+** con extensiones: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `json`, `gd` (incluidas en XAMPP).
- **MySQL 8+** (o MariaDB de XAMPP) corriendo y accesible.
- **Composer 2.x**
- **Node.js 18+** y **npm** (solo si se va a recompilar assets; no es necesario porque la UI usa CDN).
- **Git**
- Opcional: **Apache** (XAMPP) para servir en producción, o usar `php artisan serve` detrás de un proxy.
- Conexión a internet en el navegador del cliente: Bootstrap, Font Awesome, Chart.js y los mapas se cargan por CDN.

---

## 2. Obtener el código

```powershell
cd C:\xampp\php\www
git clone https://github.com/wailanbrea/sistema-prestamos-web.git "Sistema de Prestamos PHP"
cd "Sistema de Prestamos PHP"
composer install --no-dev --optimize-autoloader
```

> `composer install` ejecuta `composer dump-autoload`, lo cual carga `app/helpers.php`
> (helpers `currency()` y `company_setting()`). Si editas el autoload manualmente,
> recuerda correr `composer dump-autoload`.

---

## 3. Configurar el entorno (`.env`)

El archivo `.env` **no** está versionado (contiene secretos). Crea uno a partir del ejemplo:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Valores recomendados para producción (`.env`):

```dotenv
APP_NAME="Sistema Prestamista"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://TU_IP_O_DOMINIO

# Base de datos: MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_prestamos
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

> **APP_KEY**: si ya tenías una clave, consérvala; si no, `php artisan key:generate` crea una nueva.

---

## 4. Base de datos y datos

El repositorio incluye el dump `docs/data/seed-snapshot.sql` con todos los datos
actuales. Crea la base e impórtalo (no necesitas migrar ni seedear):

```powershell
$mysql = "C:\xampp\mysql\bin\mysql.exe"
# Crea la base
& $mysql -u root -e "CREATE DATABASE IF NOT EXISTS sistema_prestamos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# Importa el dump con todos los datos
cmd /c "\"$mysql\" -u root sistema_prestamos < \"docs\\data\\seed-snapshot.sql\""
```

> En producción usa un usuario MySQL dedicado (no `root`) con contraseña fuerte y
> ajusta `DB_USERNAME` / `DB_PASSWORD` en el `.env`.

### Si prefieres empezar con datos limpios

```powershell
php artisan migrate:fresh --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=DatabaseSeeder --force
# (opcional) cartera de demostración:
php artisan db:seed --class=DemoLoanPortfolioSeeder --force
```

### Aplicar nuevas migraciones (en actualizaciones futuras)

```powershell
php artisan migrate --force
```

---

## 5. Cachés y arranque

```powershell
php artisan config:clear
php artisan route:clear
php artisan view:clear
# En producción puedes cachear para velocidad:
php artisan config:cache
php artisan route:cache
```

### Servir la aplicación

**Opción A — servidor embebido (rápido):**
```powershell
php artisan serve --host=0.0.0.0 --port=8000
```
Para mantenerlo corriendo como servicio en Windows usa **NSSM** o el **Programador de tareas**
apuntando a ese comando.

**Opción B — Apache (XAMPP):** crea un VirtualHost cuyo `DocumentRoot` apunte a la carpeta
**`public`** del proyecto:
```apache
<VirtualHost *:80>
    ServerName prestamos.tudominio.com
    DocumentRoot "C:/xampp/php/www/Sistema de Prestamos PHP/public"
    <Directory "C:/xampp/php/www/Sistema de Prestamos PHP/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## 6. Usuarios actuales (incluidos en la base de datos)

| Email | Nombre | Rol | Contraseña | Estado |
|-------|--------|-----|------------|--------|
| `admin@sistemaprestamista.local` | Administrador Demo | **Administrador** | `Password123!` | Activo |
| `cobrador@sistemaprestamista.local` | Carlos Cobrador | **Cobrador** | `Password123!` | Activo |

> ⚠️ **Cambia estas contraseñas tras el primer acceso en producción.**
> Las contraseñas se guardan con hash (bcrypt). Los roles usan *teams* de
> `spatie/laravel-permission`, asociados a la empresa (`company_id`).

---

## 7. Empresa y licencia (plan)

| Empresa | Plan / Licencia | Estado |
|---------|-----------------|--------|
| **Prestamista Demo RD** (id 1) | **Plan Prestamista** | Activo |

- El plan se controla en **Configuración → Plan / Licencia** y define qué menús ve la empresa:
  - **Plan Prestamista**: Dashboard, Clientes, Cotizaciones, Préstamos, Cobros (+ Configuración y Roles para gestión). No ve cobradores, rutas, gastos, caja, reportes ni documentos.
  - **Plan Full Prestamista**: acceso completo.
- Definición de planes: `config/plans.php`.

---

## 8. Datos actuales incluidos (al momento del despliegue)

- **1 empresa** (Prestamista Demo RD, Plan Prestamista, moneda RD$).
- **2 usuarios** (ver tabla arriba).
- **3 clientes**.
- **21 préstamos** (19 activos, 1 en mora, 1 saldado).
- **14 pagos** registrados.
- Roles y permisos sembrados: Administrador, Supervisor, Cobrador, Caja/Contabilidad, Legal.

---

## 9. Notas operativas

- **Moneda**: configurable en Configuración (RD$ / US$); se aplica en toda la UI y documentos PDF mediante el helper `currency()`.
- **Prefijos** de préstamo/recibo/cotización: configurables; se usan al generar los números.
- **Aprobación de préstamos** y **pago parcial**: interruptores en Configuración que activan esos flujos.
- **Documentos PDF** (recibos, pagarés): usan `barryvdh/laravel-dompdf`, no requieren servicios externos.
- **Backups**: respalda la base con `mysqldump -u root sistema_prestamos > backup.sql` periódicamente (y los archivos de `storage/`).
- **Pruebas**: `php artisan test` (95+ pruebas) valida el sistema antes de cada despliegue.

---

## 10. Checklist rápido de despliegue

```text
[ ] git clone + composer install --no-dev
[ ] .env creado + php artisan key:generate
[ ] APP_ENV=production, APP_DEBUG=false, APP_URL correcto
[ ] DB_* del .env apuntan a tu MySQL (host/base/usuario/clave)
[ ] CREATE DATABASE sistema_prestamos + importar docs\data\seed-snapshot.sql
[ ] php artisan migrate --force   (si hay migraciones nuevas)
[ ] php artisan config:cache route:cache
[ ] Servir con php artisan serve (NSSM) o Apache → /public
[ ] Login admin OK + cambiar contraseñas
```

# Despliegue en VPS Windows — Sistema de Préstamos

Guía para desplegar el sistema en un VPS Windows (XAMPP / PHP 8.2). El repositorio
incluye un **snapshot con todos los datos actuales** en `docs/data/seed-snapshot.sqlite`
(empresa, usuarios, clientes, préstamos y pagos). La base de datos viva
(`database/database.sqlite`) está ignorada por git (buena práctica); en el despliegue
se copia el snapshot a esa ruta, dejando el sistema "llave en mano".

- **Repositorio:** https://github.com/wailanbrea/sistema-prestamos-web
- **Rama:** `main`
- **Stack:** Laravel 12 · PHP 8.2 · SQLite (por defecto) · Bootstrap 5 (vía CDN, no requiere build de Vite)

---

## 1. Requisitos en el VPS

- **PHP 8.2+** con extensiones: `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `json`, `gd` (incluidas en XAMPP).
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

# Base de datos: SQLite con los datos incluidos en el repo
DB_CONNECTION=sqlite
# (no hace falta DB_HOST/DB_DATABASE para SQLite; usa database/database.sqlite)

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

> **APP_KEY**: si ya tenías una clave, consérvala; si no, `php artisan key:generate` crea una nueva.

---

## 4. Base de datos y datos

El repositorio incluye el snapshot `docs/data/seed-snapshot.sqlite` con todos los datos
actuales. Cópialo a la ruta de la base viva (no necesitas migrar ni seedear):

```powershell
# Copia el snapshot con todos los datos a la base de datos viva
Copy-Item "docs\data\seed-snapshot.sqlite" "database\database.sqlite" -Force
# Verifica (debe pesar ~550 KB)
Get-Item database\database.sqlite
# Asegura permisos de escritura para el usuario que corre PHP/Apache
icacls "database\database.sqlite" /grant "Everyone:(M)"
icacls "database" /grant "Everyone:(M)"
```

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
- **Backups**: por ser SQLite, basta con copiar `database/database.sqlite` periódicamente.
- **Pruebas**: `php artisan test` (95+ pruebas) valida el sistema antes de cada despliegue.

---

## 10. Checklist rápido de despliegue

```text
[ ] git clone + composer install --no-dev
[ ] .env creado + php artisan key:generate
[ ] APP_ENV=production, APP_DEBUG=false, APP_URL correcto
[ ] Copy-Item docs\data\seed-snapshot.sqlite -> database\database.sqlite (escribible)
[ ] php artisan migrate --force   (si hay migraciones nuevas)
[ ] php artisan config:cache route:cache
[ ] Servir con php artisan serve (NSSM) o Apache → /public
[ ] Login admin OK + cambiar contraseñas
```

#requires -Version 5.1
<#
.SYNOPSIS
    Prepara el Sistema de Prestamos recien clonado: dependencias, .env, base de
    datos MySQL y compilacion de assets.

.DESCRIPTION
    El sistema usa MySQL 8. La base de datos NO se versiona; en el repo viaja un
    dump con todos los datos actuales en docs/data/seed-snapshot.sql (empresa,
    plan, clientes, prestamos, pagos, roles, etc.). Sin importar ese dump (o
    correr los seeders), al clonar no hay datos ni se ven los graficos del
    dashboard.

    Por defecto importa el SNAPSHOT (datos completos). Con -Seed usa migraciones
    + seeders (portafolio de demo reducido).

.PARAMETER Database
    Nombre de la base MySQL. Por defecto 'sistema_prestamos'.

.PARAMETER DbUser
    Usuario MySQL. Por defecto 'root'.

.PARAMETER DbPassword
    Contrasena MySQL. Por defecto vacia (default de XAMPP).

.PARAMETER MysqlBin
    Carpeta bin de MySQL. Por defecto 'C:\xampp\mysql\bin'.

.PARAMETER Seed
    Construye la base con migraciones + seeders en lugar de importar el dump.

.PARAMETER Dev
    Deja Vite corriendo (npm run dev) en lugar del build de produccion.

.EXAMPLE
    ./setup.ps1                 # datos completos desde el dump
.EXAMPLE
    ./setup.ps1 -Seed           # demo reducido con seeders
.EXAMPLE
    ./setup.ps1 -DbUser app -DbPassword secreta
#>
[CmdletBinding()]
param(
    [string]$Database = 'sistema_prestamos',
    [string]$DbUser = 'root',
    [string]$DbPassword = '',
    [string]$MysqlBin = 'C:\xampp\mysql\bin',
    [switch]$Seed,
    [switch]$Dev
)

$ErrorActionPreference = 'Stop'
Set-Location -Path $PSScriptRoot

function Write-Step($message) {
    Write-Host ""
    Write-Host ">> $message" -ForegroundColor Cyan
}

function Assert-Command($name, $hint) {
    if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
        throw "No se encontro '$name'. $hint"
    }
}

$mysqlExe = Join-Path $MysqlBin 'mysql.exe'
if (-not (Test-Path $mysqlExe)) {
    throw "No se encontro mysql.exe en '$MysqlBin'. Pasa -MysqlBin con la ruta correcta."
}

# Construye los argumentos de credenciales para el cliente mysql.
$cred = @("-u$DbUser")
if ($DbPassword -ne '') { $cred += "-p$DbPassword" }

Write-Step "Verificando herramientas requeridas"
Assert-Command php 'Instala PHP 8.2+ y agregalo al PATH.'
Assert-Command composer 'Instala Composer (https://getcomposer.org).'
Assert-Command npm 'Instala Node.js 18+ (https://nodejs.org).'

Write-Step "Instalando dependencias PHP (composer install)"
composer install

Write-Step "Instalando dependencias JS (npm install)"
npm install

if (-not (Test-Path '.env')) {
    Write-Step "Creando .env desde .env.example"
    Copy-Item '.env.example' '.env'
} else {
    Write-Host ">> .env ya existe, se conserva" -ForegroundColor DarkGray
}

Write-Step "Generando la app key"
php artisan key:generate

Write-Step "Asegurando la base MySQL '$Database'"
& $mysqlExe @cred -e "CREATE DATABASE IF NOT EXISTS ``$Database`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if ($LASTEXITCODE -ne 0) { throw "No se pudo conectar/crear la base MySQL. Revisa credenciales (-DbUser/-DbPassword) y que MySQL este corriendo." }

if ($Seed) {
    Write-Step "Migrando y sembrando la base (migrate:fresh --seed)"
    php artisan migrate:fresh --seed --force
} else {
    $dump = Join-Path $PSScriptRoot 'docs/data/seed-snapshot.sql'
    if (-not (Test-Path $dump)) {
        throw "No se encontro el dump en $dump. Usa -Seed para construir la base con seeders."
    }
    Write-Step "Importando datos desde el snapshot (docs/data/seed-snapshot.sql)"
    Get-Content $dump -Raw | & $mysqlExe @cred $Database
    if ($LASTEXITCODE -ne 0) { throw "Fallo la importacion del dump." }
    Write-Host ">> Base importada con todos los datos actuales." -ForegroundColor DarkGray
}

if ($Dev) {
    Write-Step "Iniciando Vite en modo desarrollo (npm run dev)"
    Write-Host "Deten con Ctrl+C cuando termines." -ForegroundColor DarkGray
    npm run dev
} else {
    Write-Step "Compilando assets (npm run build)"
    npm run build
}

Write-Host ""
Write-Host "Listo. Arranca la app con:  php artisan serve" -ForegroundColor Green
Write-Host "Acceso admin:  admin@sistemaprestamista.local  /  Password123!" -ForegroundColor Green

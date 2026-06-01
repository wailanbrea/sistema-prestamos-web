<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CashMovementController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CollectorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanQuoteController;
use App\Http\Controllers\ModulePlaceholderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ZoneController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'user.active', 'company.active', 'permission.company', 'menu.visible'])->group(function (): void {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');

    Route::prefix('clientes')->name('clients.')->controller(ClientController::class)->group(function (): void {
        Route::get('/', 'index')->middleware('permission:clients.view')->name('index');
        Route::get('/crear', 'create')->middleware('permission:clients.create')->name('create');
        Route::post('/', 'store')->middleware('permission:clients.create')->name('store');
        Route::get('/{client}', 'show')->whereNumber('client')->middleware('permission:clients.view')->name('show');
        Route::get('/{client}/editar', 'edit')->whereNumber('client')->middleware('permission:clients.update')->name('edit');
        Route::put('/{client}', 'update')->whereNumber('client')->middleware('permission:clients.update')->name('update');
        Route::delete('/{client}', 'destroy')->whereNumber('client')->middleware('permission:clients.delete')->name('destroy');
    });
    Route::prefix('cotizaciones')->name('loan-quotes.')->controller(LoanQuoteController::class)->middleware('permission:quotes.manage')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{quote}', 'show')->whereNumber('quote')->name('show');
        Route::delete('/{quote}', 'destroy')->whereNumber('quote')->middleware('permission:quotes.delete')->name('destroy');
    });
    Route::prefix('prestamos')->name('loans.')->controller(LoanController::class)->group(function (): void {
        Route::get('/', 'index')->middleware('permission:loans.view')->name('index');
        Route::get('/crear', 'create')->middleware('permission:loans.create')->name('create');
        Route::post('/', 'store')->middleware('permission:loans.create')->name('store');
        Route::post('/preview', 'preview')->middleware('permission:loans.create')->name('preview');
        Route::get('/{loan}', 'show')->whereNumber('loan')->middleware('permission:loans.view')->name('show');
        Route::get('/{loan}/editar', 'edit')->whereNumber('loan')->middleware('permission:loans.update')->name('edit');
        Route::put('/{loan}', 'update')->whereNumber('loan')->middleware('permission:loans.update')->name('update');
        Route::post('/{loan}/aprobar', 'approve')->whereNumber('loan')->middleware('permission:loans.approve')->name('approve');
        Route::delete('/{loan}', 'destroy')->whereNumber('loan')->middleware('permission:loans.delete')->name('destroy');
    });
    Route::prefix('cobros')->name('payments.')->controller(PaymentController::class)->middleware('permission:payments.create')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/prestamo/{loan}/cuotas', 'installments')->whereNumber('loan')->name('loan-installments');
        Route::get('/{payment}', 'show')->whereNumber('payment')->name('show');
        Route::post('/{payment}/anular', 'cancel')->whereNumber('payment')->middleware('permission:payments.cancel')->name('cancel');
    });
    Route::prefix('cobradores')->name('collectors.')->controller(CollectorController::class)->middleware('permission:collectors.manage')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{collector}', 'show')->whereNumber('collector')->name('show');
        Route::get('/{collector}/editar', 'edit')->whereNumber('collector')->name('edit');
        Route::put('/{collector}', 'update')->whereNumber('collector')->name('update');
        Route::delete('/{collector}', 'destroy')->whereNumber('collector')->name('destroy');
    });
    Route::prefix('rutas')->name('routes.')->controller(RouteController::class)->middleware('permission:routes.manage')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/mapa', 'map')->name('map');
        Route::get('/seguimiento', 'tracking')->name('tracking');
        Route::get('/seguimiento/datos', 'trackingData')->name('tracking.data');
        Route::get('/historial-seguimiento', 'trackingHistory')->name('tracking.history');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{route}', 'show')->whereNumber('route')->name('show');
        Route::get('/{route}/editar', 'edit')->whereNumber('route')->name('edit');
        Route::put('/{route}', 'update')->whereNumber('route')->name('update');
        Route::delete('/{route}', 'destroy')->whereNumber('route')->name('destroy');
    });
    Route::prefix('rutas/zonas')->name('zones.')->controller(ZoneController::class)->middleware('permission:routes.manage')->group(function (): void {
        Route::post('/', 'store')->name('store');
        Route::delete('/{zone}', 'destroy')->whereNumber('zone')->name('destroy');
    });
    Route::prefix('gastos')->name('expenses.')->controller(ExpenseController::class)->middleware('permission:expenses.manage')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{expense}', 'show')->whereNumber('expense')->name('show');
    });
    Route::prefix('gastos/categorias')->name('expense-categories.')->controller(ExpenseCategoryController::class)->middleware('permission:expenses.manage')->group(function (): void {
        Route::post('/', 'store')->name('store');
        Route::delete('/{category}', 'destroy')->whereNumber('category')->name('destroy');
    });
    Route::prefix('caja')->name('cash-movements.')->controller(CashMovementController::class)->middleware('permission:cash.view')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
    });
    Route::prefix('documentos')->name('documents.')->controller(DocumentController::class)->middleware('permission:documents.generate')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/prestamo', 'generateLoanDocument')->name('loan.generate');
        Route::post('/recibo-pago', 'generatePaymentReceipt')->name('payment-receipt.generate');
        Route::get('/{document}/descargar', 'download')->whereNumber('document')->name('download');
    });
    Route::prefix('reportes')->name('reports.')->controller(ReportController::class)->middleware('permission:reports.view')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/financiero.pdf', 'pdf')->name('financial.pdf');
        Route::get('/financiero.csv', 'csv')->name('financial.csv');
    });
    Route::prefix('configuracion')->middleware('permission:settings.manage')->group(function (): void {
        Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/', [SettingsController::class, 'update'])->name('settings.update');
    });
    Route::prefix('usuarios')->name('users.')->controller(UserController::class)->middleware('permission:users.manage')->group(function (): void {
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{user}/editar', 'edit')->whereNumber('user')->name('edit');
        Route::put('/{user}', 'update')->whereNumber('user')->name('update');
    });
    Route::prefix('roles')->name('roles.')->controller(RoleController::class)->middleware('permission:users.manage')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{role}/editar', 'edit')->whereNumber('role')->name('edit');
        Route::put('/{role}', 'update')->whereNumber('role')->name('update');
        Route::post('/{role}/duplicar', 'duplicate')->whereNumber('role')->name('duplicate');
        Route::delete('/{role}', 'destroy')->whereNumber('role')->name('destroy');
    });
});

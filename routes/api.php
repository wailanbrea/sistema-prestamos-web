<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\AdminController;
use App\Http\Controllers\Api\V2\AdminReportController;
use App\Http\Controllers\Api\V2\AuthController;
use App\Http\Controllers\Api\V2\CashboxController;
use App\Http\Controllers\Api\V2\CollectorController;
use App\Http\Controllers\Api\V2\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('v2')->name('api.v2.')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');

    Route::middleware(['auth:sanctum', 'user.active', 'company.active', 'permission.company'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::get('/dashboard', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');

        Route::prefix('collector')->name('collector.')->middleware('permission:collector.access')->group(function (): void {
            Route::get('/summary', [CollectorController::class, 'summary'])->name('summary');
            Route::get('/map-clients', [CollectorController::class, 'mapClients'])->name('map-clients');
            Route::get('/routes', [CollectorController::class, 'routes'])->name('routes');
            Route::get('/route-sessions/active', [CollectorController::class, 'activeRouteSession'])->name('route-sessions.active');
            Route::post('/route-sessions', [CollectorController::class, 'startRouteSession'])->name('route-sessions.start');
            Route::post('/route-sessions/{session}/locations', [CollectorController::class, 'recordRouteLocation'])->whereNumber('session')->name('route-sessions.locations');
            Route::post('/route-sessions/{session}/finish', [CollectorController::class, 'finishRouteSession'])->whereNumber('session')->name('route-sessions.finish');
            Route::get('/clients', [CollectorController::class, 'clients'])->name('clients');
            Route::get('/clients/{client}', [CollectorController::class, 'client'])->name('clients.show');
            Route::get('/loans', [CollectorController::class, 'loans'])->name('loans');
            Route::get('/loans/{loan}', [CollectorController::class, 'loan'])->name('loans.show');
            Route::get('/loans/{loan}/documents', [CollectorController::class, 'loanDocuments'])->whereNumber('loan')->name('loans.documents');
            Route::post('/loans/{loan}/documents', [CollectorController::class, 'generateLoanDocument'])->whereNumber('loan')->name('loans.documents.generate');
            Route::get('/installments', [CollectorController::class, 'installments'])->name('installments');
            Route::get('/installments/{installment}', [CollectorController::class, 'installment'])->name('installments.show');
            Route::get('/payments', [CollectorController::class, 'payments'])->name('payments');
            Route::get('/payments/{payment}', [CollectorController::class, 'payment'])->name('payments.show');
            Route::post('/payments', [CollectorController::class, 'storePayment'])->name('payments.store');
        });

        // Back-office (Administrador/Supervisor/Caja). Cada endpoint gated por su permiso.
        // Nota: la vista GLOBAL de cartera usa `collectors.manage` (Admin/Supervisor) en vez de
        // `clients.view`/`loans.view`, porque el Cobrador tiene esos permisos para su propia
        // cartera y no debe ver la cartera completa de la empresa (eso va por /collector/*).
        Route::prefix('admin')->name('admin.')->group(function (): void {
            Route::get('/clients', [AdminController::class, 'clients'])->middleware('permission:collectors.manage')->name('clients');
            // clients.create se valida en el FormRequest (mismo contrato que la web).
            Route::post('/clients', [AdminController::class, 'storeClient'])->middleware('permission:collectors.manage')->name('clients.store');
            Route::get('/clients/{client}', [AdminController::class, 'client'])->middleware('permission:collectors.manage')->whereNumber('client')->name('clients.show');

            // Cotizaciones (mismo gate que la web: quotes.manage).
            Route::get('/quotes', [AdminController::class, 'quotes'])->middleware('permission:quotes.manage')->name('quotes');
            Route::post('/quotes', [AdminController::class, 'storeQuote'])->middleware('permission:quotes.manage')->name('quotes.store');
            Route::get('/quotes/{quote}', [AdminController::class, 'quote'])->middleware('permission:quotes.manage')->whereNumber('quote')->name('quotes.show');
            Route::delete('/quotes/{quote}', [AdminController::class, 'destroyQuote'])->middleware('permission:quotes.manage')->whereNumber('quote')->name('quotes.destroy');
            Route::get('/collectors', [AdminController::class, 'collectors'])->middleware('permission:collectors.manage')->name('collectors');
            Route::get('/loans', [AdminController::class, 'loans'])->middleware('permission:collectors.manage')->name('loans');
            Route::post('/loans', [AdminController::class, 'storeLoan'])->middleware('permission:loans.create')->name('loans.store');
            Route::put('/loans/{loan}', [AdminController::class, 'updateLoan'])->middleware('permission:loans.update')->whereNumber('loan')->name('loans.update');
            Route::get('/loans/{loan}', [AdminController::class, 'loan'])->middleware('permission:collectors.manage')->whereNumber('loan')->name('loans.show');
            Route::get('/loans/{loan}/documents', [AdminController::class, 'loanDocuments'])->middleware('permission:collectors.manage')->whereNumber('loan')->name('loans.documents');
            Route::post('/loans/{loan}/documents', [AdminController::class, 'generateLoanDocument'])->middleware('permission:documents.generate')->whereNumber('loan')->name('loans.documents.generate');

            // Cobro desde back-office: exige cartera global ADEMÁS de payments.create,
            // para que un Cobrador (que también tiene payments.create) no pueda cobrar
            // préstamos fuera de su cartera por esta vía.
            Route::post('/payments', [AdminController::class, 'storePayment'])->middleware(['permission:collectors.manage', 'permission:payments.create'])->name('payments.store');

            Route::get('/approvals', [AdminController::class, 'approvals'])->middleware('permission:loans.approve')->name('approvals');
            Route::post('/loans/{loan}/approve', [AdminController::class, 'approveLoan'])->middleware('permission:loans.approve')->whereNumber('loan')->name('loans.approve');
            Route::post('/loans/{loan}/reject', [AdminController::class, 'rejectLoan'])->middleware('permission:loans.approve')->whereNumber('loan')->name('loans.reject');

            Route::post('/registration-links', [AdminController::class, 'createRegistrationLink'])->middleware('permission:collectors.manage')->name('registration-links.store');

            Route::get('/reports/summary', [AdminReportController::class, 'summary'])->middleware('permission:reports.view')->name('reports.summary');
            Route::get('/reports/collectors', [AdminReportController::class, 'collectors'])->middleware('permission:reports.view')->name('reports.collectors');
        });

        // Caja / Contabilidad: gastos (expenses.manage) y caja (cash.view).
        Route::prefix('cashbox')->name('cashbox.')->group(function (): void {
            Route::get('/expenses', [CashboxController::class, 'expenses'])->middleware('permission:expenses.manage')->name('expenses');
            Route::post('/expenses', [CashboxController::class, 'storeExpense'])->middleware('permission:expenses.manage')->name('expenses.store');
            Route::get('/expense-categories', [CashboxController::class, 'expenseCategories'])->middleware('permission:expenses.manage')->name('expense-categories');
            Route::get('/movements', [CashboxController::class, 'movements'])->middleware('permission:cash.view')->name('movements');
            Route::get('/summary', [CashboxController::class, 'summary'])->middleware('permission:cash.view')->name('summary');
        });
    });
});

<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\AccountPayableController;
use App\Http\Controllers\Api\V2\AdminClientController;
use App\Http\Controllers\Api\V2\AdminCollectorController;
use App\Http\Controllers\Api\V2\AdminLoanController;
use App\Http\Controllers\Api\V2\AdminPaymentController;
use App\Http\Controllers\Api\V2\AdminQuoteController;
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
            Route::get('/clients', [AdminClientController::class, 'clients'])->middleware('permission:collectors.manage')->name('clients');
            Route::post('/clients', [AdminClientController::class, 'storeClient'])->middleware('permission:collectors.manage')->name('clients.store');
            Route::get('/clients/{client}', [AdminClientController::class, 'client'])->middleware('permission:collectors.manage')->whereNumber('client')->name('clients.show');
            Route::put('/clients/{client}', [AdminClientController::class, 'updateClient'])->middleware('permission:clients.update')->whereNumber('client')->name('clients.update');
            Route::delete('/clients/{client}', [AdminClientController::class, 'deleteClient'])->middleware('permission:collectors.manage')->whereNumber('client')->name('clients.destroy');

            // Cotizaciones (mismo gate que la web: quotes.manage).
            Route::get('/quotes', [AdminQuoteController::class, 'quotes'])->middleware('permission:quotes.manage')->name('quotes');
            Route::post('/quotes', [AdminQuoteController::class, 'storeQuote'])->middleware('permission:quotes.manage')->name('quotes.store');
            Route::get('/quotes/{quote}', [AdminQuoteController::class, 'quote'])->middleware('permission:quotes.manage')->whereNumber('quote')->name('quotes.show');
            Route::delete('/quotes/{quote}', [AdminQuoteController::class, 'destroyQuote'])->middleware('permission:quotes.manage')->whereNumber('quote')->name('quotes.destroy');
            Route::get('/collectors', [AdminCollectorController::class, 'collectors'])->middleware('permission:collectors.manage')->name('collectors');
            Route::post('/collectors', [AdminCollectorController::class, 'storeCollector'])->middleware('permission:collectors.manage')->name('collectors.store');
            Route::get('/collectors/{collector}', [AdminCollectorController::class, 'showCollector'])->middleware('permission:collectors.manage')->whereNumber('collector')->name('collectors.show');
            Route::put('/collectors/{collector}', [AdminCollectorController::class, 'updateCollector'])->middleware('permission:collectors.manage')->whereNumber('collector')->name('collectors.update');
            Route::post('/collectors/{collector}/commissions/{commission}/pay', [AdminCollectorController::class, 'payCollectorCommission'])->middleware('permission:collectors.manage')->whereNumber('collector')->whereNumber('commission')->name('collectors.commissions.pay');
            Route::get('/loans', [AdminLoanController::class, 'loans'])->middleware('permission:collectors.manage')->name('loans');
            Route::post('/loans', [AdminLoanController::class, 'storeLoan'])->middleware('permission:loans.create')->name('loans.store');
            Route::put('/loans/{loan}', [AdminLoanController::class, 'updateLoan'])->middleware('permission:loans.update')->whereNumber('loan')->name('loans.update');
            Route::delete('/loans/{loan}/installments/{installment}/late-fee', [AdminLoanController::class, 'waiveInstallmentLateFee'])->middleware('permission:loans.update')->whereNumber('loan')->whereNumber('installment')->name('loans.installments.late-fee.destroy');
            Route::delete('/loans/{loan}', [AdminLoanController::class, 'deleteLoan'])->middleware('permission:collectors.manage')->whereNumber('loan')->name('loans.destroy');
            Route::get('/loans/{loan}', [AdminLoanController::class, 'loan'])->middleware('permission:collectors.manage')->whereNumber('loan')->name('loans.show');
            Route::get('/loans/{loan}/documents', [AdminLoanController::class, 'loanDocuments'])->middleware('permission:collectors.manage')->whereNumber('loan')->name('loans.documents');
            Route::post('/loans/{loan}/documents', [AdminLoanController::class, 'generateLoanDocument'])->middleware('permission:documents.generate')->whereNumber('loan')->name('loans.documents.generate');

            // Contratos digitales: generar y consultar el contrato de un préstamo desde la app.
            Route::get('/loans/{loan}/contract', [AdminLoanController::class, 'loanContract'])->middleware('permission:legal.manage')->whereNumber('loan')->name('loans.contract');
            Route::post('/loans/{loan}/contract', [AdminLoanController::class, 'generateLoanContract'])->middleware('permission:legal.manage')->whereNumber('loan')->name('loans.contract.generate');

            // Cobro desde back-office: exige cartera global ADEMÁS de payments.create,
            // para que un Cobrador (que también tiene payments.create) no pueda cobrar
            // préstamos fuera de su cartera por esta vía.
            Route::get('/payments', [AdminPaymentController::class, 'payments'])->middleware('permission:collectors.manage')->name('payments.index');
            Route::get('/payments/{payment}', [AdminPaymentController::class, 'payment'])->middleware('permission:collectors.manage')->whereNumber('payment')->name('payments.show');
            Route::post('/payments', [AdminPaymentController::class, 'storePayment'])->middleware(['permission:collectors.manage', 'permission:payments.create'])->name('payments.store');
            Route::post('/payments/{payment}/cancel', [AdminPaymentController::class, 'cancelPayment'])->middleware('permission:payments.cancel')->whereNumber('payment')->name('payments.cancel');
            Route::post('/cash/movements', [AdminPaymentController::class, 'storeMovement'])->middleware('permission:cash.view')->name('cash.movements.store');

            Route::get('/approvals', [AdminLoanController::class, 'approvals'])->middleware('permission:loans.approve')->name('approvals');
            Route::post('/loans/{loan}/approve', [AdminLoanController::class, 'approveLoan'])->middleware('permission:loans.approve')->whereNumber('loan')->name('loans.approve');
            Route::post('/loans/{loan}/reject', [AdminLoanController::class, 'rejectLoan'])->middleware('permission:loans.approve')->whereNumber('loan')->name('loans.reject');

            Route::post('/registration-links', [AdminClientController::class, 'createRegistrationLink'])->middleware('permission:collectors.manage')->name('registration-links.store');

            Route::get('/reports/summary', [AdminReportController::class, 'summary'])->middleware('permission:reports.view')->name('reports.summary');
            Route::get('/reports/collectors', [AdminReportController::class, 'collectors'])->middleware('permission:reports.view')->name('reports.collectors');
            Route::get('/reports/catalog', [AdminReportController::class, 'catalog'])->middleware('permission:reports.view')->name('reports.catalog');

            Route::prefix('accounts-payable')->name('accounts-payable.')->middleware('permission:accounts-payable.manage')->group(function (): void {
                Route::get('/', [AccountPayableController::class, 'index'])->name('index');
                Route::post('/', [AccountPayableController::class, 'store'])->name('store');
                Route::get('/creditors', [AccountPayableController::class, 'creditors'])->name('creditors');
                Route::post('/creditors', [AccountPayableController::class, 'storeCreditor'])->name('creditors.store');
                Route::put('/creditors/{creditor}', [AccountPayableController::class, 'updateCreditor'])->whereNumber('creditor')->name('creditors.update');
                Route::delete('/creditors/{creditor}', [AccountPayableController::class, 'destroyCreditor'])->whereNumber('creditor')->name('creditors.destroy');
                Route::get('/{accountPayable}', [AccountPayableController::class, 'show'])->whereNumber('accountPayable')->name('show');
                Route::put('/{accountPayable}', [AccountPayableController::class, 'update'])->whereNumber('accountPayable')->name('update');
                Route::post('/{accountPayable}/payments', [AccountPayableController::class, 'storePayment'])->whereNumber('accountPayable')->name('payments.store');
                Route::delete('/{accountPayable}', [AccountPayableController::class, 'destroy'])->whereNumber('accountPayable')->name('destroy');
            });
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

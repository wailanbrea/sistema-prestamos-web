<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\AuthController;
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

        Route::prefix('collector')->name('collector.')->middleware('permission:payments.create')->group(function (): void {
            Route::get('/summary', [CollectorController::class, 'summary'])->name('summary');
            Route::get('/clients', [CollectorController::class, 'clients'])->name('clients');
            Route::get('/clients/{client}', [CollectorController::class, 'client'])->name('clients.show');
            Route::get('/loans', [CollectorController::class, 'loans'])->name('loans');
            Route::get('/loans/{loan}', [CollectorController::class, 'loan'])->name('loans.show');
            Route::get('/installments', [CollectorController::class, 'installments'])->name('installments');
            Route::get('/installments/{installment}', [CollectorController::class, 'installment'])->name('installments.show');
            Route::get('/payments', [CollectorController::class, 'payments'])->name('payments');
            Route::get('/payments/{payment}', [CollectorController::class, 'payment'])->name('payments.show');
            Route::post('/payments', [CollectorController::class, 'storePayment'])->name('payments.store');
        });
    });
});

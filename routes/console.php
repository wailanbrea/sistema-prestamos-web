<?php

use App\Services\Loans\LateStatusRefreshService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('loans:refresh-late-status {--company_id= : Limit refresh to one company ID}', function (LateStatusRefreshService $service): int {
    $companyId = $this->option('company_id') ? (int) $this->option('company_id') : null;
    $stats = $service->refresh($companyId);

    $this->info('Moras y atrasos actualizados.');
    $this->table(['Métrica', 'Valor'], collect($stats)->map(fn (int $value, string $key) => [$key, $value])->values()->all());

    return self::SUCCESS;
})->purpose('Refresh overdue installments, late loans and delinquent clients.');

Schedule::command('loans:refresh-late-status')->dailyAt('00:10');

<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\LoanInstallment;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $companyId = (int) (auth()->user()?->company_id ?? 0);

            $view->with('navigationSections', config('navigation.sections', []));
            $view->with('operationAlerts', $companyId > 0 ? [
                'missing_coordinates' => Client::query()
                    ->forCompany($companyId)
                    ->where(function ($query): void {
                        $query->whereNull('latitude')->orWhereNull('longitude');
                    })
                    ->count(),
                'late_installments' => LoanInstallment::query()
                    ->where('status', 'late')
                    ->whereHas('loan', fn ($query) => $query->forCompany($companyId))
                    ->count(),
            ] : [
                'missing_coordinates' => 0,
                'late_installments' => 0,
            ]);
        });
    }
}

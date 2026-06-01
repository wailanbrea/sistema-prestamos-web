<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\LoanInstallment;
use App\Support\MenuAccess;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
            $user = auth()->user();
            $companyId = (int) ($user?->company_id ?? 0);

            $sections = config('navigation.sections', []);
            if ($user) {
                $sections = collect($sections)
                    ->map(function (array $section) use ($user): array {
                        $section['items'] = array_values(array_filter(
                            $section['items'] ?? [],
                            static fn (array $item): bool => MenuAccess::isItemVisible($user, $item),
                        ));

                        return $section;
                    })
                    ->filter(static fn (array $section): bool => count($section['items']) > 0)
                    ->values()
                    ->all();
            }

            $view->with('navigationSections', $sections);
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

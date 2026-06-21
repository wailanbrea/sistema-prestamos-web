<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\LoanInstallment;
use App\Models\User;
use App\Support\MenuAccess;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        // Detrás de Cloudflare/proxy el TLS se termina en el borde y la app
        // recibe HTTP, por lo que route()/url() y las URLs firmadas salen con
        // esquema http y el navegador las bloquea por mixed content. Fuera de
        // local forzamos https para que coincida con cómo se sirve el sitio.
        if (! $this->app->environment('local')) {
            URL::forceScheme('https');
        }

        // El dueño del sistema (super-admin) supera toda verificación de
        // permisos. Devolver null deja que el chequeo normal continúe.
        Gate::before(static fn (User $user): ?bool => $user->isSystemOwner() ? true : null);

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
            $view->with('unreadNotifications', $user
                ? $user->unreadNotifications()->latest()->limit(6)->get()
                : collect());
            $view->with('unreadNotificationsCount', $user ? $user->unreadNotifications()->count() : 0);
            $view->with('operationAlerts', $companyId > 0 ? [
                'missing_coordinates' => Client::query()
                    ->forCompany($companyId)
                    ->where(function ($query): void {
                        $query->whereNull('latitude')->orWhereNull('longitude');
                    })
                    ->count(),
                'late_installments' => LoanInstallment::query()
                    ->whereIn('status', ['pending', 'partial', 'late'])
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->whereHas('loan', fn ($query) => $query->forCompany($companyId))
                    ->count(),
            ] : [
                'missing_coordinates' => 0,
                'late_installments' => 0,
            ]);
        });
    }
}

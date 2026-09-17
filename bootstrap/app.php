<?php

use App\Http\Middleware\EnsureCompanyIsActive;
use App\Http\Middleware\EnsureMenuIsVisible;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\PreventDemoModifications;
use App\Http\Middleware\ServiceTerminated;
use App\Http\Middleware\SetPermissionCompanyContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Detrás de Cloudflare/proxy el TLS se termina en el borde y la app
        // recibe HTTP. Sin confiar en X-Forwarded-Proto, $request->url() se
        // reconstruye como http:// al validar las URLs firmadas (que se generan
        // como https://), la firma no coincide y el enlace de descarga del
        // estado de cuenta/recibos devuelve 403. Confiamos en el proxy para que
        // isSecure() y la validación de la firma usen https.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_AWS_ELB);

        $middleware->append(ServiceTerminated::class);

        $middleware->alias([
            'company.active' => EnsureCompanyIsActive::class,
            'menu.visible' => EnsureMenuIsVisible::class,
            'permission' => PermissionMiddleware::class,
            'permission.company' => SetPermissionCompanyContext::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'user.active' => EnsureUserIsActive::class,
            'demo.protect' => PreventDemoModifications::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpException $exception, Request $request) {
            if (
                $exception->getStatusCode() === 419
                && $request->isMethod('post')
                && $request->is('login')
            ) {
                return redirect()
                    ->route('login')
                    ->withErrors([
                        'email' => 'La sesion del formulario vencio o cambio en otra pestaña. Intenta iniciar sesion nuevamente.',
                    ]);
            }

            return null;
        });
    })->create();

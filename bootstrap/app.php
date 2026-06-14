<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'company.active' => \App\Http\Middleware\EnsureCompanyIsActive::class,
            'menu.visible' => \App\Http\Middleware\EnsureMenuIsVisible::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'permission.company' => \App\Http\Middleware\SetPermissionCompanyContext::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'user.active' => \App\Http\Middleware\EnsureUserIsActive::class,
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

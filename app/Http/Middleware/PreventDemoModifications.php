<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventDemoModifications
{
    /**
     * Rutas exentas de la restricción de modo demo (por nombre de ruta o path relativo).
     *
     * @var list<string>
     */
    protected array $exceptRouteNames = [
        'logout',
        'api.v2.auth.logout',
        'loans.preview',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'isDemo') || ! $user->isDemo()) {
            return $next($request);
        }

        // Métodos seguros de lectura siempre permitidos
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Permitir rutas específicas como logout o simulaciones en vivo
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        $message = 'Acción no permitida: Las cuentas demo están en modo solo lectura (no pueden crear, editar ni eliminar registros).';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
            ], Response::HTTP_FORBIDDEN);
        }

        return back()->with('error', $message);
    }

    /**
     * Determina si la petición debe pasar sin restricciones.
     */
    protected function shouldPassThrough(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if ($routeName && in_array($routeName, $this->exceptRouteNames, true)) {
            return true;
        }

        // Fallback por URL para endpoints críticos de cierre de sesión o previsualización
        if ($request->is('logout') || $request->is('*/logout') || $request->is('prestamos/preview')) {
            return true;
        }

        return false;
    }
}

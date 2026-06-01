<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\MenuAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuIsVisible
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if ($user && ! MenuAccess::allowsRoute($user, $routeName)) {
            abort(403, 'No tienes acceso a esta sección.');
        }

        return $next($request);
    }
}

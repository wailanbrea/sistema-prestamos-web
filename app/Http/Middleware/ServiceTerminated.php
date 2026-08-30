<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceTerminated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('up') || ! config('system.service_terminated')) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'message' => 'Servicio Terminado',
            ], 503);
        }

        return response()->view('service-terminated', status: 503);
    }
}

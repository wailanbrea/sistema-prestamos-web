<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->company_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $request->user()->company_id);
        }

        return $next($request);
    }
}

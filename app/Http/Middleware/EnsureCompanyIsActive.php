<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->company?->status === 'active') {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'La empresa asociada está inactiva.',
            ], 403);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'La empresa asociada está inactiva.',
        ]);
    }
}

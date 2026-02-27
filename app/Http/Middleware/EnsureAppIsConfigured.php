<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppIsConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        $hasUsers = User::query()->exists();
        $isSetupRoute = $request->routeIs('setup.*');

        // Always allow health/API and static asset routes to work before setup.
        if ($request->is('api/*') || $request->is('up') || $request->is('storage/*')) {
            return $next($request);
        }

        if (!$hasUsers && !$isSetupRoute) {
            return redirect()->route('setup.show');
        }

        if ($hasUsers && $isSetupRoute) {
            return redirect('/login');
        }

        return $next($request);
    }
}

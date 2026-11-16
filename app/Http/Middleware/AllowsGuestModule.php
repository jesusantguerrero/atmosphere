<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marker read by GuardGuestAccess: `guest.module:shopping` opens a route to guests holding that grant.
 */
class AllowsGuestModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        return $next($request);
    }
}

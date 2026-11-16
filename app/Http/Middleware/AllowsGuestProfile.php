<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marker read by GuardGuestAccess: `guest.profile` opens a route to guests granted the
 * route's `{profileId}`, or any profile when the route has none and scopes itself.
 */
class AllowsGuestProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}

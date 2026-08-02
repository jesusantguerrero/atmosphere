<?php

namespace App\Http\Middleware;

use App\Domains\AppCore\Facades\Feature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate a route on a feature flag. Usage:
 *
 *   Route::get('/trends/relationships', ...)
 *       ->middleware('feature:trends-relationships');
 *
 * Resolution uses the authenticated user's context — user override wins,
 * then team override, then global default. Returns 404 (not 403) so the
 * route stays undiscoverable when off; matches how "unshipped feature"
 * URLs typically behave elsewhere in the app.
 */
class FeatureFlag
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        $active = Feature::activeForUser($key, $request->user());

        if (! $active) {
            abort(404);
        }

        return $next($request);
    }
}

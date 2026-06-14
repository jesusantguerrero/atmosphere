<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Today merged into Dashboard — always send authenticated users to
                // /dashboard. The legacy `landing_page='today'` Setting is now a
                // no-op; the /today route itself redirects to /dashboard so any
                // stale link still lands on the right page.
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}

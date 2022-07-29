<?php

namespace Freesgen\Atmosphere\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;

class EnsureHasNoTeam
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $currentUser = $request->user();
        if ($currentUser->allTeams()->count()) {
            return Redirect(RouteServiceProvider::HOME);
        }
        return $next($request);
    }
}

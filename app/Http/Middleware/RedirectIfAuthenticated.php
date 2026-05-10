<?php

namespace App\Http\Middleware;

use App\Models\Setting;
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
                $user = Auth::guard($guard)->user();
                $home = RouteServiceProvider::HOME;

                $landingPage = Setting::where([
                    'user_id' => $user->id,
                    'team_id' => $user->current_team_id,
                    'name' => 'landing_page',
                ])->value('value');

                if ($landingPage === 'today') {
                    $home = '/today';
                }

                return redirect($home);
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Events\Menu\AppCreated;
use App\Concerns\Facades\Menu;
use Closure;

class AppMenu
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check if logged in
        if (!auth()->check()) {
            return $next($request);
        }

        Menu::create('app', function ($menu) {
            event(new AppCreated($menu));
        });

        return $next($request);
    }
}

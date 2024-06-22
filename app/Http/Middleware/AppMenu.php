<?php

namespace App\Http\Middleware;

use Closure;
use App\Concerns\Facades\Menu;
use App\Events\Menu\AppCreated;

class AppMenu
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
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

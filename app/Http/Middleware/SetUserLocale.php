<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    /**
     * @var array<int, string>
     */
    protected array $supported = ['en', 'es'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $locale = $user?->language;

        if ($locale && in_array($locale, $this->supported, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}

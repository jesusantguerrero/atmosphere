<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    public const LOCALE_COOKIE = 'loger_locale';

    /**
     * @var array<int, string>
     */
    protected array $supported = ['en', 'es'];

    public function handle(Request $request, Closure $next): Response
    {
        $userLocale = $this->supportedLocale($request->user()?->language);
        $locale = $userLocale ?? $this->supportedLocale($request->cookie(self::LOCALE_COOKIE));

        if ($locale) {
            App::setLocale($locale);
        }

        /**
         * Guest screens have no user to read the language from, so a session that
         * ends mid-work drops the user on a login page in English — including the
         * message explaining what just happened. Remember the last signed-in
         * language so those screens stay in it.
         */
        if ($userLocale && $userLocale !== $request->cookie(self::LOCALE_COOKIE)) {
            Cookie::queue(self::LOCALE_COOKIE, $userLocale, 60 * 24 * 365);
        }

        return $next($request);
    }

    protected function supportedLocale(?string $locale): ?string
    {
        return $locale && in_array($locale, $this->supported, true) ? $locale : null;
    }
}

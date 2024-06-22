<?php

namespace App\Http\Middleware;

use Closure;
use App\Exceptions\UnauthorizedModule;
use Symfony\Component\HttpFoundation\Response;

class EnsureCoreModulePermissions
{

    public function handle($request, Closure $next, $coreModuleName, $guard = null): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        if (!auth()->user()->currentTeam->isModuleEnabled($coreModuleName)) {
            throw new UnauthorizedModule();
        }

        return $next($request);
    }
}

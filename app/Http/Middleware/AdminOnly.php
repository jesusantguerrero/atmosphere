<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate the `/admin/*` prefix to users with the `admin` or `super_admin`
 * role. Any other authenticated user gets a 403 (not a 404) so the resource
 * is discoverable but not accessible — makes debugging easier for staff
 * without leaking the URL space to strangers.
 *
 * The `superadmin` alias lets destructive routes (delete team, hard-delete
 * user, kill-switch flags) restrict themselves further by chaining
 * `->middleware('admin.only:superadmin')`.
 */
class AdminOnly
{
    public function handle(Request $request, Closure $next, string $tier = 'admin'): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $allowed = match ($tier) {
            'superadmin' => $user->isSuperAdmin(),
            default => $user->isAdmin(),
        };

        if (! $allowed) {
            abort(403, 'Admin access required.');
        }

        return $next($request);
    }
}

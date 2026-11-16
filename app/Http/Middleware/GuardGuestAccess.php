<?php

namespace App\Http\Middleware;

use App\Models\TeamMemberGrant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deny by default for guest members: on any signed-in route a guest only gets through
 * account self-service and routes that declare a module (`guest.module:shopping`) they
 * hold a grant for. Reads need a read grant; anything else needs write.
 */
class GuardGuestAccess
{
    /**
     * Their own account, security, notifications and switching or joining households.
     */
    const ACCOUNT_ROUTES = [
        'logout',
        'profile.show',
        'user.security',
        'user.preferences',
        'user-profile-information.update',
        'user-password.update',
        'current-user-photo.destroy',
        'current-user.destroy',
        'other-browser-sessions.destroy',
        'two-factor.*',
        'password.confirm',
        'password.confirm.store',
        'password.confirmation',
        'verification.*',
        'user.data-export.*',
        'user.notification-prefs',
        'user.connected-apps.destroy',
        'notifications',
        'notifications.*',
        'current-team.update',
        'team-invitations.accept',
        'api.team-invitations.*',
        'mobile.me',
        'mobile.me.update',
        'mobile.me.password.*',
        'mobile.me.sessions',
        'mobile.me.sessions.*',
        'mobile.me.two-factor.*',
        'mobile.me.notification-preferences',
        'mobile.me.notification-preferences.*',
        'mobile.notifications.*',
        'mobile.session.destroy',
        'mobile.current-team.update',
        'mobile.account.delete',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $team = $user?->currentTeam;
        $route = $request->route();

        if (! $route instanceof Route || ! $user?->isGuestOf($team) || ! $this->requiresSignIn($route) || $route->named(self::ACCOUNT_ROUTES)) {
            return $next($request);
        }

        $module = $this->declaredModule($route);
        $access = $request->isMethodSafe() ? TeamMemberGrant::ACCESS_READ : TeamMemberGrant::ACCESS_WRITE;

        abort_unless($module && $user->hasModuleGrant($team, $module, $access), 403, __('This has not been shared with you.'));

        return $next($request);
    }

    private function requiresSignIn(Route $route): bool
    {
        return collect($route->gatherMiddleware())->contains(
            fn ($middleware) => is_string($middleware) && (str_starts_with($middleware, 'auth') || str_contains($middleware, 'Authenticate'))
        );
    }

    private function declaredModule(Route $route): ?string
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'guest.module:')) {
                return substr($middleware, strlen('guest.module:'));
            }
        }

        return null;
    }
}

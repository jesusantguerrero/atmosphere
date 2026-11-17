<?php

namespace App\Http\Middleware;

use App\Domains\LogerProfile\Models\LogerProfileEntity;
use App\Models\TeamMemberGrant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Modules\Plan\Entities\Plan;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deny by default for guest members: on any signed-in route a guest only gets through
 * account self-service and routes marked `guest.profile` for a profile they were granted.
 * Reads need a read grant; anything else needs write.
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
        'mobile.team-invitations.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $team = $user?->currentTeam;
        $route = $request->route();

        if (! $route instanceof Route || ! $user?->isGuestOf($team) || ! $this->requiresSignIn($route) || $route->named(self::ACCOUNT_ROUTES)) {
            return $next($request);
        }

        $access = $request->isMethodSafe() ? TeamMemberGrant::ACCESS_READ : TeamMemberGrant::ACCESS_WRITE;
        $reached = $this->profilesReached($route, (int) $team->id);
        $granted = $user->grantedProfileIds($team, $access);

        abort_unless(
            $this->opensToGuests($route) && ($reached === null ? $granted !== [] : array_intersect($reached, $granted) !== []),
            403,
            __('This has not been shared with you.')
        );

        return $next($request);
    }

    /**
     * The profiles a route acts on: its `{profileId}`, or those linked to its `{plan}` (a
     * shopping list). Null when the route names neither and scopes itself.
     *
     * @return array<int, int>|null
     */
    private function profilesReached(Route $route, int $teamId): ?array
    {
        if ($route->hasParameter('profileId')) {
            return [(int) $route->parameter('profileId')];
        }

        if ($route->hasParameter('plan')) {
            $plan = $route->parameter('plan');

            return LogerProfileEntity::query()->without('entity')->where('team_id', $teamId)
                ->where('entity_type', Plan::class)->where('entity_id', (int) ($plan instanceof Plan ? $plan->id : $plan))
                ->pluck('profile_id')->map(fn ($id) => (int) $id)->all();
        }

        return null;
    }

    private function requiresSignIn(Route $route): bool
    {
        return collect($route->gatherMiddleware())->contains(
            fn ($middleware) => is_string($middleware) && (str_starts_with($middleware, 'auth') || str_contains($middleware, 'Authenticate'))
        );
    }

    private function opensToGuests(Route $route): bool
    {
        return in_array('guest.profile', $route->gatherMiddleware(), true);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Freesgen\Atmosphere\Http\Middleware\EnsureUserHasTeam;
use Illuminate\Http\Request;

/**
 * The package's team gate redirects to onboarding, which an API client reads as
 * an HTML page. API callers get JSON instead: 409 `no_household` with the
 * invitations they can accept, and a missing current team is just picked.
 */
class EnsureUserHasHousehold extends EnsureUserHasTeam
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! ($request->is('api/*') || $request->wantsJson())) {
            return parent::handle($request, $next);
        }

        if (! $user->allTeams()->count()) {
            return response()->json([
                'message' => __('You do not belong to a household yet.'),
                'code' => 'no_household',
                'pending_invitations' => $user->pendingInvitationsPayload(),
            ], 409);
        }

        $this->ensureOneOfTeamIsCurrent($user);

        return $next($request);
    }
}

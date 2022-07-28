<?php

namespace Freesgen\Atmosphere\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureUserHasTeam
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $currentUser = $request->user();
        if ($currentUser) {
            if (!$currentUser->allTeams()->count()) {
                return Redirect()->route('onboarding.index');
            } else if($this->ensureOneOfTeamIsCurrent($currentUser)) {
                return Redirect('/dashboard');
            }
        }
        return $next($request);
    }

    protected function ensureOneOfTeamIsCurrent(User $currentUser) {
        if (!is_null($currentUser->current_team_id)) {
            return;
        }

        $firstTeamId = $currentUser->allTeams()->first()->id;
        $currentUser->current_team_id = $firstTeamId;
        $currentUser->save();
        return true;
    }
}

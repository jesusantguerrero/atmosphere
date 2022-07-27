<?php

namespace App\Http\Controllers\Jetstream;

use App\Actions\Jetstream\InviteTeamMember;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Jetstream;

class TeamInvitationController {
      /**
     * Add a new team member to a team.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $teamId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resend(Request $request, $invitationId)
    {
        $invitation = TeamInvitation::find($invitationId);

        if (Features::sendsTeamInvitations()) {
            app(InviteTeamMember::class)->resend(
                $request->user(),
                $invitation->team,
                $invitation,
            );
        }

        return back(303);
    }

}

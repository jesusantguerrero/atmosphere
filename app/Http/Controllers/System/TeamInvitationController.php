<?php

namespace App\Http\Controllers\System;

use App\Actions\Jetstream\InviteTeamMember;
use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Jetstream\Contracts\AddsTeamMembers;
use Laravel\Jetstream\Features;

class TeamInvitationController
{
    /**
     * Re send an existing invitation to the user email.
     *
     * @param  int  $teamId
     * @return RedirectResponse
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

    /**
     * Re send an existing invitation to the user email.
     *
     * @param  int  $teamId
     * @return RedirectResponse
     */
    public function accept(Request $request, $invitationId)
    {
        $invitation = TeamInvitation::findOrFail($invitationId);
        abort_unless(strcasecmp($request->user()->email, $invitation->email) === 0, 403, __('This invitation was sent to another email.'));

        app(AddsTeamMembers::class)->add(
            $invitation->team->owner,
            $invitation->team,
            $invitation->email,
            $invitation->role
        );

        $invitation->delete();
        $request->user()->fresh()->switchTeam($invitation->team);

        return redirect(config('fortify.home'))->banner(
            __('Great! You have accepted the invitation to join the :team team.', ['team' => $invitation->team->name]),
        );
    }

    /**
     * Re send an existing invitation to the user email.
     *
     * @param  int  $teamId
     * @return RedirectResponse
     */
    public function reject(Request $request, $invitationId)
    {
        $invitation = TeamInvitation::findOrFail($invitationId);
        abort_unless(strcasecmp($request->user()->email, $invitation->email) === 0, 403, __('This invitation was sent to another email.'));

        $invitation->delete();

        return back(303);
    }
}

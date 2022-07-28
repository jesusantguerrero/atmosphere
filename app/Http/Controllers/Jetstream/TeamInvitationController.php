<?php

namespace App\Http\Controllers\Jetstream;

use App\Actions\Jetstream\InviteTeamMember;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Jetstream;

class TeamInvitationController {
    /**
     * Re send an existing invitation to the user email.
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

    /**
     * Re send an existing invitation to the user email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $teamId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function accept($invitationId)
    {
        $invitation = TeamInvitation::find($invitationId);
        if (auth()->user()->email !== $invitation->team->email) {
            return response()->json([
                'message' => 'You are not authorized to accept this invitation.',
            ], 403);
        }

        app(AddsTeamMembers::class)->add(
            $invitation->team->owner,
            $invitation->team,
            $invitation->email,
            $invitation->role
        );

        $invitation->delete();

        return redirect(config('fortify.home'))->banner(
            __('Great! You have accepted the invitation to join the :team team.', ['team' => $invitation->team->name]),
        );
    }

    /**
     * Re send an existing invitation to the user email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $teamId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject($invitationId)
    {
        $invitation = TeamInvitation::reject($invitationId);
        if (auth()->user()->email !== $invitation->email) {
            return response()->json([
                'message' => 'You are not authorized to accept this invitation.',
            ], 403);
        }

        $invitation->delete();
        return back(303);
    }

}

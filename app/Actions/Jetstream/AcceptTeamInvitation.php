<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Laravel\Jetstream\Contracts\AddsTeamMembers;

/**
 * Joins the invitee to the household with the invited role and makes it their
 * current one. Joining verifies the email (see AcceptInvitation listener): the
 * invitation reached that inbox.
 */
class AcceptTeamInvitation
{
    public function __construct(private AddsTeamMembers $addsTeamMembers) {}

    public function accept(User $user, TeamInvitation $invitation): Team
    {
        $this->ensureInvitationIsFor($user, $invitation);
        $team = $invitation->team;

        $this->addsTeamMembers->add($team->owner, $team, $invitation->email, $invitation->role);
        $invitation->delete();
        $user->fresh()->switchTeam($team);

        return $team;
    }

    public function reject(User $user, TeamInvitation $invitation): void
    {
        $this->ensureInvitationIsFor($user, $invitation);

        $invitation->delete();
    }

    private function ensureInvitationIsFor(User $user, TeamInvitation $invitation): void
    {
        abort_unless(strcasecmp($user->email, $invitation->email) === 0, 403, __('This invitation was sent to another email.'));
    }
}

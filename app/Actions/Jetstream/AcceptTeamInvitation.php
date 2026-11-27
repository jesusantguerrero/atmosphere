<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMemberGrant;
use App\Models\User;
use Laravel\Jetstream\Contracts\AddsTeamMembers;

/**
 * Joins the invitee to the household with the invited role and makes it their
 * current one. Joining verifies the email (see AcceptInvitation listener): the
 * invitation reached that inbox. A member already in the household (e.g. a guest
 * invited again as editor) takes the invited role instead.
 */
class AcceptTeamInvitation
{
    public function __construct(private AddsTeamMembers $addsTeamMembers, private SyncGuestGrants $guestGrants) {}

    public function accept(User $user, TeamInvitation $invitation): Team
    {
        $this->ensureInvitationIsFor($user, $invitation);
        $team = $invitation->team;

        if ($team->hasUser($user)) {
            $this->changeRole($user, $team, $invitation);
        } else {
            $this->addsTeamMembers->add($team->owner, $team, $invitation->email, $invitation->role);
        }
        $invitation->delete();
        $user->fresh()->switchTeam($team);

        return $team;
    }

    public function reject(User $user, TeamInvitation $invitation): void
    {
        $this->ensureInvitationIsFor($user, $invitation);

        $invitation->delete();
    }

    /**
     * The owner keeps owning; anyone else takes the invited role, and leaving the guest
     * role drops the per-profile grants it needed.
     */
    private function changeRole(User $user, Team $team, TeamInvitation $invitation): void
    {
        if ($user->ownsTeam($team)) {
            return;
        }

        $team->users()->updateExistingPivot($user->id, ['role' => $invitation->role]);

        if ($invitation->role === TeamMemberGrant::GUEST_ROLE) {
            $this->guestGrants->sync($team, $user, $invitation->grants ?? []);
        } else {
            $this->guestGrants->revoke($team, $user);
        }
    }

    private function ensureInvitationIsFor(User $user, TeamInvitation $invitation): void
    {
        abort_unless(strcasecmp($user->email, $invitation->email) === 0, 403, __('This invitation was sent to another email.'));
    }
}

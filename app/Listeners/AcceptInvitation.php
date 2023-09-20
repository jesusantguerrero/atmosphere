<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Verified;
use Laravel\Jetstream\Events\TeamMemberAdded;

class AcceptInvitation
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TeamMemberAdded $teamMemberAdded)
    {
        if (! $teamMemberAdded->member->user->hasVerifiedEmail()) {
            $teamMemberAdded->member->user->markEmailAsVerified();
            event(new Verified($teamMemberAdded->member->user));
        }
    }
}

<?php

namespace App\Domains\Transaction\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Insane\Journal\Models\Accounting\Reconciliation;

class ReconciliationPolicy
{
    public function adjust(User $user, Reconciliation $reconciliation) {
        return $user->current_team_id === $reconciliation->team_id 
        ? Response::allow()
        : Response::deny('You do not own this post.');
    }

    public function update(User $user, Reconciliation $reconciliation) {

        return $user->id == $reconciliation->user_id;
    }

    public function delete(User $user, Reconciliation $reconciliation) {

        return $user->id == $reconciliation->user_id;
    }
}

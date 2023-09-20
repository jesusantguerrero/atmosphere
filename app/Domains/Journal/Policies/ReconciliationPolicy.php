<?php

namespace App\Domains\Journal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Insane\Journal\Models\Accounting\Reconciliation;

class ReconciliationPolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
        return true;
    }

    public function view(User $user, Reconciliation $reconciliation)
    {
        return $user->team_id == $reconciliation->team_id;
    }

    public function update(User $user, Reconciliation $reconciliation)
    {
        return $user->team_id == $reconciliation->team_id;
    }

    public function delete(User $user, Reconciliation $reconciliation)
    {
        return $user->team_id == $reconciliation->team_id;
    }
}

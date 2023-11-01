<?php

namespace App\Domains\Journal\Policies;

use App\Models\User;
use Insane\Journal\Models\Core\Transaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Transaction $transaction)
    {
        return $user->current_team_id == $transaction->team_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Transaction $transaction)
    {
        return $user->current_team_id == $transaction->team_id;
    }

    public function updateBulk(User $user)
    {
        return $user->current_team_id;
    }

    public function deleteBulk(User $user)
    {
        return $user->current_team_id;
    }

    public function delete(User $user, Transaction $transaction)
    {
        return $user->current_team_id == $transaction->team_id;
    }
}

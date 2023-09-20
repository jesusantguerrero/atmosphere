<?php

namespace App\Domains\Journal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Insane\Journal\Models\Core\Transaction;

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

    public function delete(User $user, Transaction $transaction)
    {
       return $user->current_team_id == $transaction->team_id;
    }
}

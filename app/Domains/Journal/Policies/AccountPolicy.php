<?php

namespace App\Domains\Journal\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Insane\Journal\Models\Core\Account;

class AccountPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }


    public function view(User $user, Account $account)
    {
        return $user->team_id == $account->team_id;
    }


    public function create(User $user)
    {
        return true;
    }


    public function update(User $user, Account $account)
    {
        return $user->team_id == $account->team_id;
    }


    public function delete(User $user, Account $account)
    {
        return $user->team_id == $account->team_id;
    }
}

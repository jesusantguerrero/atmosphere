<?php

namespace App\Domains\AppCore\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Insane\Journal\Models\Core\Account;

class FinanceAccountPolicy
{
    public function show(User $user, Account $account)
    {
        return $this->ownsAccount($user, $account)
        ? Response::allow()
        : Response::deny('You do not own this account.');
    }

    public function update(User $user, Account $account)
    {
        return $this->ownsAccount($user, $account)
        ? Response::allow()
        : Response::deny('You do not own this account.');
    }

    public function create(User $user)
    {

        return true;
    }

    public function delete(User $user, Account $account)
    {

        return $this->ownsAccount($user, $account);
    }

    private function ownsAccount(User $user, Account $account): bool
    {
        return (int) $user->current_team_id === (int) $account->team_id;
    }
}

<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\AccountUpdates;
use Insane\Journal\Models\Core\Account;

class AccountUpdate implements AccountUpdates
{
    public function update(User $user, Account $account, array $accountData): Account
    {
        $this->validate($user, $account);
        $account->update($accountData);
        return $account;
    }

    public function validate(User $user, Account $account)
    {
        Gate::forUser($user)->authorize('update', $account);
    }
}

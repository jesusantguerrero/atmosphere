<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\AccountCreates;
use Insane\Journal\Models\Core\Account;

class AccountCreate implements AccountCreates
{

    public function create(User $user, array $accountData): Account
    {
        $this->validate($user);
        $account = new Account();
        $account = Account::create([
            ...$accountData,
            "team_id" => $user->current_team_id,
            "user_id" => $user->id,
        ]);

        return $account;
    }

    public function validate(mixed $user)
    {
        Gate::forUser($user)->authorize('create', Account::class);
    }
}



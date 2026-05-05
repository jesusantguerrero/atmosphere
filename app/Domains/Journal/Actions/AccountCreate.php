<?php

namespace App\Domains\Journal\Actions;

use App\Models\Account;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\AccountCreates;

class AccountCreate implements AccountCreates
{
    public function create(User $user, array $accountData): Account
    {
        // Use Loger's Account (extends Insane\Journal\Models\Core\Account) so the
        // mass-assign goes through its constructor merge that adds `is_multi_currency`
        // and `secondary_currencies` to fillable. Otherwise LM-8's strict mode throws.
        $this->validate($user);

        return Account::create([
            ...$accountData,
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
        ]);
    }

    public function validate(mixed $user)
    {
        Gate::forUser($user)->authorize('create', Account::class);
    }
}

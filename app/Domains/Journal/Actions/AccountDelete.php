<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Insane\Journal\Contracts\AccountDeletes;
use Insane\Journal\Models\Core\Account;

class AccountDelete implements AccountDeletes
{
    public function delete(User $user, Account $account)
    {
        $this->validate($user, $account);
        $account->delete();
    }

    public function validate(User $user, mixed $account)
    {
        Gate::forUser($user)->authorize('delete-account', $account);
        if (count($account->transactions)) {
            throw ValidationException::withMessages([
                'account' => __('You may not delete account with transactions.'),
            ])->errorBag('deleteAccount');
        }
    }
}

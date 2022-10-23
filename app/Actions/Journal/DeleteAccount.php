<?php

namespace App\Actions\Journal;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Insane\Journal\Contracts\DeleteAccounts;

class DeleteAccount implements DeleteAccounts
{
    public function delete(mixed $account)
    {
        if ($account->payee && $account->team_id == $account->team_id) {
            $account->payee->delete();
        }
        $account->delete();
    }

    public function validate(mixed $user, mixed $account)
    {
        Gate::forUser($user)->authorize('delete-account', $account);
        if (count($account->transactions)) {
            throw ValidationException::withMessages([
                'account' => __('You may not delete account with transactions.'),
            ])->errorBag('deleteAccount');
        }
    }
}

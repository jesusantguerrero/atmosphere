<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\TransactionUpdates;
use Insane\Journal\Models\Core\Transaction;

class TransactionUpdate implements TransactionUpdates
{
    public function validate(User $user, Transaction $transaction)
    {
        Gate::forUser($user)->authorize('update', $transaction);
    }

    public function update(User $user, Transaction $transaction, array $data)
    {
        $this->validate($user, $transaction);
        $transaction->updateTransaction($data);

        return $transaction;
    }
}

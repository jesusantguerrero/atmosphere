<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\TransactionApproves;
use Insane\Journal\Models\Core\Transaction;

class TransactionApprove implements TransactionApproves
{
    public function validate(User $user, Transaction $transaction)
    {
        Gate::forUser($user)->authorize('update', $transaction);
    }

    public function approve(User $user, Transaction $transaction)
    {
        $this->validate($user, $transaction);
        $transaction->approve();
    }
}

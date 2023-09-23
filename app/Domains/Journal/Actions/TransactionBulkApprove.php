<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\TransactionBulkApproves;
use Insane\Journal\Models\Core\Transaction;

class TransactionBulkApprove implements TransactionBulkApproves
{
    public function validate(User $user)
    {
        Gate::forUser($user)->authorize('update', Transaction::class);
    }

    public function approveAllDrafts(User $user)
    {
        $this->validate($user);
        $transactions = Transaction::where([
            'user_id' => $user->id,
            'status' => Transaction::STATUS_DRAFT,
        ])->get();

        foreach ($transactions as $transaction) {
            $transaction->approve();
        }
    }
}

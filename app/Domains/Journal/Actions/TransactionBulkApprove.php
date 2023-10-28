<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Models\Core\Transaction;
use Insane\Journal\Contracts\TransactionBulkApproves;

class TransactionBulkApprove implements TransactionBulkApproves
{
    public function validate(User $user)
    {
        Gate::forUser($user)->authorize('updateBulk', Transaction::class);
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

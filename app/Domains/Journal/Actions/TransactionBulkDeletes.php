<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\TransactionBulkDeletes;
use Insane\Journal\Models\Core\Transaction;

class TransactionBulkDelete implements TransactionBulkDeletes {
    public function validate(User $user) {
        Gate::forUser($user)->authorize('bulk-delete', Transaction::class);   
    }
    public function deleteAllDrafts(User $user) {
        $this->validate($user);
        $transactions = Transaction::where([
            'user_id'=> $user->id,
            'status' => 'draft'
        ])->get();
        foreach ($transactions as $transaction) {
            $transaction->remove();
        }
    }
}

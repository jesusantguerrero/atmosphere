<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\TransactionDeletes;
use Insane\Journal\Models\Core\Transaction;

class TransactionDelete implements TransactionDeletes {
    public function validate(User $user, Transaction $transaction) {
        Gate::forUser($user)->authorize('delete', Transaction::class);   
    }
    public function delete(User $user, Transaction $transaction) {
        $this->validate($user, $transaction);
        $transaction->remove();
    }
}

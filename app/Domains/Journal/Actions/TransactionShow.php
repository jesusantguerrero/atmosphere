<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Models\Core\Transaction;
use Insane\Journal\Contracts\TransactionShows;

class TransactionShow implements TransactionShows
{
    public function validate(User $user, Transaction $transaction)
    {
        Gate::forUser($user)->authorize('view', $transaction);
    }

    public function show(User $user, Transaction $transaction)
    {
        $this->validate($user, $transaction);

        return [
            ...$transaction->toArray(),
            'lines' => $transaction->lines,
        ];
    }
}

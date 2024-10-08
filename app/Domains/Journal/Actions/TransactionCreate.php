<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\TransactionCreates;
use Insane\Journal\Models\Core\Transaction;

class TransactionCreate implements TransactionCreates
{
    public function validate(User $user)
    {
        Gate::forUser($user)->authorize('create', Transaction::class);
    }

    public function create(User $user, array $transactionData)
    {
        return Transaction::createTransaction([
            ...$transactionData,
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
        ]);
    }
}

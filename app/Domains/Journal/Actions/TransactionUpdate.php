<?php

namespace App\Domains\Journal\Actions;

use App\Domains\Journal\Actions\Concerns\EnsuresTeamReferences;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\TransactionUpdates;
use Insane\Journal\Models\Core\Transaction;

class TransactionUpdate implements TransactionUpdates
{
    use EnsuresTeamReferences;

    public function validate(User $user, Transaction $transaction)
    {
        Gate::forUser($user)->authorize('update', $transaction);
    }

    public function update(User $user, Transaction $transaction, array $data)
    {
        $this->validate($user, $transaction);

        $data['team_id'] = $transaction->team_id;
        unset($data['user_id']);
        $this->ensureTeamReferences($transaction->team_id, $data);

        $transaction->updateTransaction($data);

        return $transaction;
    }
}

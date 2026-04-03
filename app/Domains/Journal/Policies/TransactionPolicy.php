<?php

namespace App\Domains\Journal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Insane\Journal\Models\Accounting\ReconciliationEntry;
use Insane\Journal\Models\Core\Transaction;

class TransactionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Transaction $transaction)
    {
        return $user->current_team_id == $transaction->team_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Transaction $transaction)
    {
        return $user->current_team_id == $transaction->team_id;
    }

    public function updateBulk(User $user)
    {
        return $user->current_team_id;
    }

    public function deleteBulk(User $user)
    {
        return $user->current_team_id;
    }

    public function delete(User $user, Transaction $transaction)
    {
        if ($user->current_team_id != $transaction->team_id) {
            return false;
        }

        $isReconciled = ReconciliationEntry::where('transaction_id', $transaction->id)
            ->where('matched', true)
            ->exists();

        return ! $isReconciled;
    }
}

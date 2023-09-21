<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\TransactionLists;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Transaction;

class TransactionList implements TransactionLists
{
    public function validate(User $user)
    {
        Gate::forUser($user)->authorize('list', Transaction::class);
    }

    public function list(User $user)
    {
        $this->validate($user);

        $transactions = Transaction::where([
            'team_id' => $user->current_team_id,
        ])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate()
            ->through(function ($transaction) {
                return Transaction::parser($transaction);
            });

        $categories = Category::where('depth', 1)
            ->with('accounts', function ($query) use ($user) {
                $query->where('team_id', '=', $user->current_team_id);
            })->get();

        return compact($transactions, $categories);
    }
}

<?php

namespace App\Listeners;

use App\Models\Account;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AccountOverdraftAlert;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Events\TransactionUpdated;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Transaction;

class NotifyAccountOverdraft implements ShouldHandleEventsAfterCommit
{
    public function handle(TransactionCreated|TransactionUpdated $event): void
    {
        $transaction = $event->transaction;

        if ($transaction->status !== Transaction::STATUS_VERIFIED) {
            return;
        }

        $user = User::query()->find($transaction->user_id);
        $team = Team::query()->find($transaction->team_id);

        if (! $user || ! $team || ! $user->belongsToTeam($team)) {
            return;
        }

        $accountIds = $transaction->lines()->pluck('account_id')->filter()->unique();

        foreach ($accountIds as $accountId) {
            $transaction->getConnection()->transaction(function () use ($transaction, $user, $accountId): void {
                $account = Account::query()
                    ->where('team_id', $transaction->team_id)
                    ->whereHas('detailType', fn ($query) => $query->whereIn('name', AccountDetailType::ALL_CASH))
                    ->lockForUpdate()
                    ->find($accountId);

                if (! $account) {
                    return;
                }

                $currency = $transaction->currency_code;
                $balance = (float) $account->transactionLines()
                    ->where('team_id', $transaction->team_id)
                    ->whereHas('transaction', fn ($query) => $query
                        ->where('team_id', $transaction->team_id)
                        ->where('status', Transaction::STATUS_VERIFIED)
                        ->where('currency_code', $currency))
                    ->reorder()
                    ->selectRaw('COALESCE(SUM(amount * type), 0) as balance')
                    ->value('balance');

                $alerts = $user->unreadNotifications()
                    ->where('type', AccountOverdraftAlert::class)
                    ->where('data->team_id', $transaction->team_id)
                    ->where('data->account_id', $account->id)
                    ->where('data->currency_code', $currency);

                if ($balance >= 0) {
                    $alerts->update(['read_at' => now()]);

                    return;
                }

                if ($alerts->exists()) {
                    return;
                }

                $user->notify(new AccountOverdraftAlert($account, $balance, $currency));
            });
        }
    }
}

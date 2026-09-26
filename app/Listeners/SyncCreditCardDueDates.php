<?php

namespace App\Listeners;

use App\Domains\Transaction\Models\BillingCycle;
use Illuminate\Support\Carbon;
use Insane\Journal\Events\AccountUpdated;

class SyncCreditCardDueDates
{
    public function handle(AccountUpdated $event): void
    {
        $account = $event->account;
        if (! $account->wasChanged('credit_payment_days') || ! $account->credit_closing_day) {
            return;
        }

        BillingCycle::query()
            ->where('team_id', $account->team_id)
            ->where('account_id', $account->id)
            ->get()
            ->each(function (BillingCycle $cycle) use ($account): void {
                $cycle->due_at = Carbon::parse($cycle->end_at)
                    ->addDays(max(0, (int) ($account->credit_payment_days ?? 0)))
                    ->format('Y-m-d');
                $cycle->saveQuietly();
            });
    }
}

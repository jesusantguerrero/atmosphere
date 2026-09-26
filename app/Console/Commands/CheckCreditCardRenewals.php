<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\CreditCardRenewalAlert;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

class CheckCreditCardRenewals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-credit-card-renewals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify card owners when annual renewal is within 30 days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $notified = 0;
        $timeZones = Setting::query()
            ->where('name', 'team_timezone')
            ->pluck('value', 'team_id');

        Account::query()
            ->whereNotNull('credit_closing_day')
            ->whereNotNull('credit_renewal_month')
            ->whereBetween('credit_renewal_month', [1, 12])
            ->get()
            ->each(function (Account $account) use ($timeZones, &$notified): void {
                $today = Carbon::now($timeZones[$account->team_id] ?? 'America/Santo_Domingo')->startOfDay();
                $renewalStart = $today->copy()->month((int) $account->credit_renewal_month)->startOfMonth();
                $renewalEnd = $renewalStart->copy()->endOfMonth();
                if ($today->gt($renewalEnd)) {
                    $renewalStart->addYear();
                }

                if ($today->diffInDays($renewalStart, false) > 30) {
                    return;
                }

                $renewalYear = $renewalStart->year;
                if ($this->alreadyNotified($account, $renewalYear)) {
                    return;
                }

                $user = User::find($account->user_id);
                if (! $user) {
                    return;
                }

                $user->notify(new CreditCardRenewalAlert($account, $renewalYear));
                $notified++;
            });

        $this->info("Credit card renewal check done. Notified: {$notified}");

        return self::SUCCESS;
    }

    private function alreadyNotified(Account $account, int $renewalYear): bool
    {
        return DatabaseNotification::query()
            ->where('notifiable_id', $account->user_id)
            ->where('type', CreditCardRenewalAlert::class)
            ->where('data->account_id', $account->id)
            ->where('data->renewal_year', $renewalYear)
            ->exists();
    }
}

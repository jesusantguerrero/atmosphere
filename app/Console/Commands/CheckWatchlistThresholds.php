<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\WatchlistThresholdAlert;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Watchlist\Models\Watchlist;

class CheckWatchlistThresholds extends Command
{
    protected $signature = 'watchlists:check-thresholds';

    protected $description = 'Check watchlists with a monthly target and notify the owner once per month when the target is crossed';

    public function handle(): int
    {
        $monthStart = now()->startOfMonth()->format('Y-m-d');
        $monthEnd = now()->endOfMonth()->format('Y-m-d');
        $monthIso = now()->startOfMonth()->format('Y-m');

        $watchlists = Watchlist::query()
            ->whereNotNull('target')
            ->where('target', '>', 0)
            ->get();

        $notified = 0;

        foreach ($watchlists as $watchlist) {
            $expenses = Watchlist::expensesInRange(
                $watchlist->team_id,
                $monthStart,
                $monthEnd,
                $watchlist
            );

            $monthTotal = (float) ($expenses?->total ?? 0);
            $target = (float) $watchlist->target;

            if ($monthTotal < $target) {
                continue;
            }

            if ($this->alreadyNotifiedThisMonth($watchlist, $monthIso)) {
                continue;
            }

            $user = User::find($watchlist->user_id);
            if (! $user) {
                continue;
            }

            $user->notify(new WatchlistThresholdAlert($watchlist, $monthTotal, $monthIso));
            $notified++;
        }

        $this->info("Watchlist threshold check done. Notified: {$notified}");

        return self::SUCCESS;
    }

    private function alreadyNotifiedThisMonth(Watchlist $watchlist, string $monthIso): bool
    {
        return DatabaseNotification::query()
            ->where('notifiable_id', $watchlist->user_id)
            ->where('type', WatchlistThresholdAlert::class)
            ->where('data->watchlist_id', $watchlist->id)
            ->where('data->month', $monthIso)
            ->exists();
    }
}

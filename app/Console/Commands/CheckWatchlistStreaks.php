<?php

namespace App\Console\Commands;

use App\Domains\Budget\Models\BudgetTarget;
use App\Models\User;
use App\Notifications\WatchlistStreakAlert;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class CheckWatchlistStreaks extends Command
{
    protected $signature = 'watchlists:check-streaks';

    protected $description = 'Notify owners when a challenge-target watchlist hits a streak milestone or breaks its streak';

    /**
     * Streak lengths (in months) that earn a notification.
     */
    private const MILESTONES = [1, 3, 6, 12, 24];

    public function handle(): int
    {
        $monthIso = now()->startOfMonth()->format('Y-m');

        $targets = BudgetTarget::query()
            ->where('target_type', BudgetTarget::TYPE_CHALLENGE_UNDER_AMOUNT)
            ->whereNotNull('watchlist_id')
            ->with('watchlist')
            ->get();

        $notified = 0;

        foreach ($targets as $target) {
            $watchlist = $target->watchlist;
            if (! $watchlist) {
                continue;
            }

            $user = User::find($watchlist->user_id);
            if (! $user) {
                continue;
            }

            $streak = $target->streakInMonths();
            $lastStreak = $this->lastReportedStreak($watchlist->id, $watchlist->user_id);

            $event = $this->resolveEvent($streak, $lastStreak);
            if (! $event) {
                continue;
            }

            if ($this->alreadyNotifiedThisMonth($watchlist->id, $watchlist->user_id, $event, $monthIso)) {
                continue;
            }

            $user->notify(new WatchlistStreakAlert($watchlist, $target, $event, $streak, $monthIso));
            $notified++;
        }

        $this->info("Watchlist streak check done. Notified: {$notified}");

        return self::SUCCESS;
    }

    private function resolveEvent(int $streak, int $lastStreak): ?string
    {
        if ($lastStreak >= 1 && $streak === 0) {
            return WatchlistStreakAlert::EVENT_BREAK;
        }

        if ($streak > $lastStreak && in_array($streak, self::MILESTONES, true)) {
            return WatchlistStreakAlert::EVENT_MILESTONE;
        }

        return null;
    }

    private function lastReportedStreak(int $watchlistId, int $userId): int
    {
        $latest = DatabaseNotification::query()
            ->where('notifiable_id', $userId)
            ->where('type', WatchlistStreakAlert::class)
            ->where('data->watchlist_id', $watchlistId)
            ->where('data->event', WatchlistStreakAlert::EVENT_MILESTONE)
            ->latest('created_at')
            ->first();

        return (int) ($latest?->data['streak_months'] ?? 0);
    }

    private function alreadyNotifiedThisMonth(int $watchlistId, int $userId, string $event, string $monthIso): bool
    {
        return DatabaseNotification::query()
            ->where('notifiable_id', $userId)
            ->where('type', WatchlistStreakAlert::class)
            ->where('data->watchlist_id', $watchlistId)
            ->where('data->event', $event)
            ->where('data->month', $monthIso)
            ->exists();
    }
}

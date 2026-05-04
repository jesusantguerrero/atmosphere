<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use App\Notifications\WatchlistAutoSuggestionAlert;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Watchlist\Services\WatchlistAutoSuggestService;

class SuggestUntrackedPayees extends Command
{
    protected $signature = 'watchlists:suggest-untracked-payees {--limit=3}';

    protected $description = 'Weekly nudge: detect top-N payees this month not on any watchlist and notify the team owner.';

    public function handle(WatchlistAutoSuggestService $service): int
    {
        $monthIso = now()->startOfMonth()->format('Y-m');
        $limit = (int) $this->option('limit');
        $notified = 0;

        foreach (Team::all() as $team) {
            $suggestions = $service->getTopUntrackedPayees($team->id, now(), $limit);

            if (empty($suggestions)) {
                continue;
            }

            if ($this->alreadyNotifiedThisMonth($team->user_id, $monthIso)) {
                continue;
            }

            $user = User::find($team->user_id);
            if (! $user) {
                continue;
            }

            $user->notify(new WatchlistAutoSuggestionAlert($suggestions, $monthIso));
            $notified++;
        }

        $this->info("Watchlist suggestion sweep done. Notified: {$notified}");

        return self::SUCCESS;
    }

    private function alreadyNotifiedThisMonth(int $userId, string $monthIso): bool
    {
        return DatabaseNotification::query()
            ->where('notifiable_id', $userId)
            ->where('type', WatchlistAutoSuggestionAlert::class)
            ->where('data->month', $monthIso)
            ->exists();
    }
}

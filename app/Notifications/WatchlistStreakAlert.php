<?php

namespace App\Notifications;

use App\Domains\Budget\Models\BudgetTarget;
use Illuminate\Bus\Queueable;
use Modules\Watchlist\Models\Watchlist;

class WatchlistStreakAlert extends LogerNotification
{
    use Queueable;

    public const EVENT_MILESTONE = 'milestone';

    public const EVENT_BREAK = 'break';

    public function __construct(
        private Watchlist $watchlist,
        private BudgetTarget $target,
        private string $event,
        private int $streakMonths,
        private string $monthIso,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $message = $this->event === self::EVENT_MILESTONE
            ? "🔥 {$this->streakMonths} meses bajo el target en {$this->watchlist->name}"
            : "Se rompió la racha de {$this->watchlist->name}: este mes pasaste el target";

        return [
            'message' => $message,
            'cta' => "Ver {$this->watchlist->name}",
            'link' => "/finance/watchlist/{$this->watchlist->id}",
            'watchlist_id' => $this->watchlist->id,
            'budget_target_id' => $this->target->id,
            'event' => $this->event,
            'streak_months' => $this->streakMonths,
            'month' => $this->monthIso,
        ];
    }
}

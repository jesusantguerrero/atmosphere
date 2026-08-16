<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Modules\Watchlist\Models\Watchlist;

class WatchlistThresholdAlert extends LogerNotification
{
    use Queueable;

    public function __construct(
        private Watchlist $watchlist,
        private float $monthTotal,
        private string $monthIso,
    ) {}

    public function toArray($notifiable): array
    {
        $target = (float) $this->watchlist->target;
        $pct = $target > 0 ? round(($this->monthTotal / $target) * 100) : 0;

        return [
            'message' => "Pasaste el límite de {$this->watchlist->name}: {$pct}% del target este mes",
            'cta' => "Ver {$this->watchlist->name}",
            'link' => "/finance/watchlist/{$this->watchlist->id}",
            'watchlist_id' => $this->watchlist->id,
            'month' => $this->monthIso,
            'month_total' => $this->monthTotal,
            'target' => $target,
        ];
    }
}

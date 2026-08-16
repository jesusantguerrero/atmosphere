<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;

/**
 * WL-8: weekly nudge to add a watchlist for high-spend payees the user isn't tracking yet.
 * The link deep-links to /finance/watchlist with pre-filled payee ids so the user lands
 * on the create modal with the suggestion already populated.
 */
class WatchlistAutoSuggestionAlert extends LogerNotification
{
    use Queueable;

    /**
     * @param  array<int, array{payee_id: int, payee_name: ?string, total: float, transactions_count: int}>  $suggestions
     */
    public function __construct(
        private array $suggestions,
        private string $monthIso,
    ) {}

    public function toArray($notifiable): array
    {
        $names = array_map(fn ($s) => $s['payee_name'] ?? 'Unknown', $this->suggestions);
        $payeeIds = array_map(fn ($s) => $s['payee_id'], $this->suggestions);
        $totalSpend = array_sum(array_column($this->suggestions, 'total'));

        $deepLink = '/finance/watchlist?suggest='.implode(',', $payeeIds);

        return [
            'message' => 'Gastaste $'.number_format($totalSpend, 2).' en '.implode(', ', $names).' este mes y no los trackeas — ¿crear watchlist?',
            'cta' => 'Crear watchlist',
            'link' => $deepLink,
            'suggestions' => $this->suggestions,
            'month' => $this->monthIso,
        ];
    }
}

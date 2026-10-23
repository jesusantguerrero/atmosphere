<?php

namespace App\Listeners;

use App\Services\ShoppingVisitLinker;
use Illuminate\Support\Facades\Log;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Events\TransactionUpdated;

/** Money out may be the card charge of a shopping trip: when it is, the trip is tied to it. */
class LinkShoppingVisit
{
    public function __construct(private ShoppingVisitLinker $linker) {}

    public function handle(TransactionCreated|TransactionUpdated $event): void
    {
        try {
            $this->linker->linkTransaction($event->transaction);
        } catch (\Throwable $e) {
            // A transaction must never fail to save because a trip could not be matched to it.
            Log::warning('LinkShoppingVisit failed', ['transaction_id' => $event->transaction->id ?? null, 'error' => $e->getMessage()]);
        }
    }
}

<?php

namespace App\Listeners;

use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Events\AccountCreated;
use App\Models\Category;

class CreateBudgetCategory
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(AccountCreated $event)
    {
        if ($event->account->detailTypeId?->name == AccountDetailType::CREDIT_CARD) {
            $session = [
                "team_id" => $event->account->team_id,
                "user_id" => $event->account->user_id
            ];
            $categoryGroupId = Category::findOrCreateByName($session, 'Credit Card Payments');
            Category::findOrCreateByName($session, $event->account->name, $categoryGroupId);
        }
    }
}

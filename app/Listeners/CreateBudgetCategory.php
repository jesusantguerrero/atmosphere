<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Category;

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
            $categoryGroupId = Category::findOrCreateByName($this->session, 'Credit Card Payments', ['editable' => 'false']);
            Category::findOrCreateByName($this->session, $event->account->name, $categoryGroupId);
        }
    }
}

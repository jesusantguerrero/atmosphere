<?php

namespace App\Listeners;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Data\CategoryData;
use App\Domains\Budget\Services\BudgetCategoryService;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Events\AccountCreated;
use Insane\Journal\Events\AccountUpdated;

class CreateBudgetCategory
{
    public function handle(AccountCreated|AccountUpdated $event)
    {
        if ($event->account->detailType->name == AccountDetailType::CREDIT_CARD) {
            BudgetCategoryService::findOrCreateByName(new CategoryData(
                null,
                $event->account->team_id,
                $event->account->user_id,
                $event->account->id,
                Category::findOrCreateByName($event->account, BudgetReservedNames::CREDIT_CARD_PAYMENTS->value),
                $event->account->name
            ));
        }
    }
}

<?php

namespace App\Listeners;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Data\CategoryData;
use App\Domains\Budget\Services\BudgetCategoryService;
use Insane\Journal\Events\AccountCreated;
use Insane\Journal\Events\AccountUpdated;
use Insane\Journal\Models\Core\AccountDetailType;

class CreateBudgetCategory
{
    public function handle(AccountCreated|AccountUpdated $event)
    {
        if ($event->account->detailType?->name == AccountDetailType::CREDIT_CARD) {
            $category = Category::where([
                "team_id" => $event->account->team_id,
                "account_id" => $event->account->id
            ])->first();
            if ($category && $category->name != $event->account->name) {
                $category->name = $event->account->name;
                $category->save();
            } else {
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
}

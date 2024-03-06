<?php
namespace App\Domains\Budget\Models\Traits;

trait BudgetCategoryTrait {
    public function scopeWithCurrentSavings($query, $startDate) {
        return $query->with(['subCategories' => fn ($q) => $q->whereHas('budget', function($query) use ($startDate) {
           return $query->whereRaw('(completed_at is null or completed_at > ?)', $startDate);
        })->orWhereDoesntHave('budget')]);
    }

    /***
     *  here will the filters for:
     *  go funded
     * not funded
     * credit cards
     *  savings or goals
     *  */

}

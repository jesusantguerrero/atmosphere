<?php

namespace App\Domains\Budget\Models\Traits;

trait BudgetCategoryTrait
{
    public function scopeWithCurrentSavings($query, $startDate): mixed
    {
        return $query->with(['subCategories' => function ($q) use ($startDate) {
            $q->where(function ($q) use ($startDate) {
                $q->whereHas('budget', function ($query) use ($startDate) {
                    return $query->whereRaw('(completed_at is null or completed_at > ?)', [$startDate]);
                })->orWhereDoesntHave('budget');
            })->where(function ($q) use ($startDate) {
                $q->whereNull('account_id')
                    ->orWhereDoesntHave('account', function ($q) use ($startDate) {
                        $q->whereNotNull('closed_at')->where('closed_at', '<=', $startDate);
                    });
            });
        }]);
    }

    /***
     *  here will the filters for:
     *  go funded
     * not funded
     * credit cards
     *  savings or goals
     *  */

}

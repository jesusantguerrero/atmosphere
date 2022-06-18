<?php

namespace App\Models;

use Insane\Journal\Models\Core\Category as CoreCategory;

class Category extends CoreCategory
{
    protected $with = ['budget'];

    public function budget() {
        return $this->hasOne(Budget::class);
    }

    public function subCategories() {
        return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('index');
    }

    public function budgets() {
        return $this->hasMany(MonthBudget::class)->orderBy('month', 'desc');
    }

    public function assignBudget(string $month, mixed $postData) {
        return MonthBudget::updateOrCreate([
            'category_id' => $this->id,
            'month' => $month,
            'name' => $month,
        ], [
            'budgeted' => (double) $postData['budgeted']
        ]);
    }

    public function getBudgetInfo(string $month) {
        return [
            'budgeted' => $this->budgets->where('month', $month)->first()->budgeted ?? 0,
            'spent' => 0,
            'remaining' => 0,
        ];
    }
}

<?php

namespace App\Domains\AppCore\Models;

use App\Domains\Budget\Data\FixedCategories;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Models\BudgetMonth;
use App\Events\BudgetAssigned;
use App\Models\Team;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Category as CoreCategory;

class Category extends CoreCategory
{
    protected $with = ['budget'];

    public function team() {
        return $this->belongsTo(Team::class);
    }

    public function budget() {
        return $this->hasOne(BudgetTarget::class);
    }

    public function budgets() {
        return $this->hasMany(BudgetMonth::class)->orderBy('month', 'desc');
    }

    public function lastMonthBudget() {
        return $this->hasMany(BudgetMonth::class)->orderBy('month', 'desc')->limit(1);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\CategoryFactory::new();
    }
}

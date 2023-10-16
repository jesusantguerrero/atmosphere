<?php

namespace App\Domains\AppCore\Models;

use App\Models\Team;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use Insane\Journal\Models\Core\Category as CoreCategory;
use App\Domains\Budget\Models\Traits\BudgetCategoryTrait;

class Category extends CoreCategory
{
    use BudgetCategoryTrait;

    protected $with = ['budget'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function budget()
    {
        return $this->hasOne(BudgetTarget::class);
    }

    public function budgets()
    {
        return $this->hasMany(BudgetMonth::class)->orderBy('month', 'desc')->limit(2);
    }

    public function subCategories()
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('index');
    }

    public function lastMonthBudget()
    {
        return $this->hasOne(BudgetMonth::class)->orderBy('month', 'desc')->limit(1);
    }
}

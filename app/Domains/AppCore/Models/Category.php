<?php

namespace App\Domains\AppCore\Models;

use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use App\Models\Team;
use Insane\Journal\Models\Core\Category as CoreCategory;

class Category extends CoreCategory
{
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
        return $this->hasMany(BudgetMonth::class)->orderBy('month', 'desc');
    }

    public function subCategories()
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('index');
    }

    public function lastMonthBudget()
    {
        return $this->hasMany(BudgetMonth::class)->orderBy('month', 'desc')->limit(1);
    }
}

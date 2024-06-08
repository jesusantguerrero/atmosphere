<?php

namespace App\Domains\AppCore\Models;

use App\Models\Team;
use App\Concerns\Factory;
use Database\Factories\CategoryFactory;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Models\BudgetMatchAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Insane\Journal\Models\Core\Category as CoreCategory;
use App\Domains\Budget\Models\Traits\BudgetCategoryTrait;

class Category extends CoreCategory
{
    use BudgetCategoryTrait;
    use HasFactory;

    protected $with = ['budget', 'matchAccount'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function budget()
    {
        return $this->hasOne(BudgetTarget::class);
    }

    public function matchAccount()
    {
        return $this->hasOne(BudgetMatchAccount::class);
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

    protected static function newFactory(): Factory
    {
        return CategoryFactory::new();
    }
}

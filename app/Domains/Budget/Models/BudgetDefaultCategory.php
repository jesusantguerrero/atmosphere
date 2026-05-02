<?php

namespace App\Domains\Budget\Models;

use App\Domains\AppCore\Models\Category;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetDefaultCategory extends Model
{
    public const ROLE_SAVINGS = 'savings';

    public const ROLE_SPENDING = 'spending';

    public const SUPPORTED_ROLES = [self::ROLE_SAVINGS, self::ROLE_SPENDING];

    protected $fillable = ['team_id', 'role', 'category_id'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

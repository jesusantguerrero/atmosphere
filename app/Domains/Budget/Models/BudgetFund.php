<?php

namespace App\Domains\Budget\Models;

use App\Domains\AppCore\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Watchlist\Models\Watchlist;

class BudgetFund extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'name',
        'monthly_splits',
        'category_id',
        'watchlist_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function watchlist()
    {
        return $this->belongsTo(Watchlist::class);
    }
}

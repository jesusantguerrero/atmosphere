<?php

namespace App\Domains\Budget\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Watchlist\Models\Watchlist;
use App\Domains\AppCore\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BudgetFund extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'amount',
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

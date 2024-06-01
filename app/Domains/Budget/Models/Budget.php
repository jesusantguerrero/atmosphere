<?php

namespace App\Domains\Budget\Models;

use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Models\Core\Account;
use App\Domains\AppCore\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'color',
        'amount',
        'name',
    ];

    public function accounts()
    {
        $this->belongsToMany(Account::class);
    }

    public function categories()
    {
        $this->belongsToMany(Category::class);
    }
}

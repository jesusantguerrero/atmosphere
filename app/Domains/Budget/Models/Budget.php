<?php

namespace App\Domains\Budget\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

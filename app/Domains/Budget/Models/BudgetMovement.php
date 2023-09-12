<?php

namespace App\Domains\Budget\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class BudgetMovement extends Model
{
    use HasFactory;
    protected $fillable = [
        'team_id',
        'user_id',
        'source_category_id',
        'destination_category_id',
        'amount',
        'date'
    ];
}

<?php

namespace App\Domains\Budget\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BudgetMatchAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'category_id',
        'parent_id',
        'account_id',
        'notify',
        'completed_at'
    ];
}

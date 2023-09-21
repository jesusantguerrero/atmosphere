<?php

namespace App\Domains\Meal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealType extends Model
{
    use HasFactory;

    protected $fillable = ['team_id', 'user_id', 'name', 'description', 'menu_id', 'config', 'active'];
}

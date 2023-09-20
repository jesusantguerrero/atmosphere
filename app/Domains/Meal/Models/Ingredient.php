<?php

namespace App\Domains\Meal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = ['team_id', 'user_id', 'product_id', 'name', 'quantity', 'unit'];
}

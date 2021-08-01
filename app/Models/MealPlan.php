<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealPlan extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id','dateable_id', 'dateable_type' ,'date', 'frequency', 'automatic'];

    public function dateable() {
        return $this->morphTo('dateable');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Meal extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id','name', 'meal_type_id', 'menu_id', 'notes'];

    public function ingredients() {
        return $this->hasMany(Ingredient::class);
    }

    public function mealType() {
        return $this->belongsTo(MealType::class);
    }

    public function saveIngredients($items) {
        Ingredient::query()->where('meal_id', $this->id)->delete();
        foreach ($items as $item) {
            if (!$item['name']) continue;
            $this->ingredients()->create([
                "team_id" => $this->team_id,
                "user_id" => $this->user_id,
                "meal_id" => $this->id,
                "quantity" => $item['quantity'],
                "name" => $item['name'],
                "unit" => $item['unit']
            ]);
        }
    }
}

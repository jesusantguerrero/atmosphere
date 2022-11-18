<?php

namespace App\Domains\Meal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Insane\Journal\Models\Product\Product;

class Meal extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id','name', 'meal_type_id', 'menu_id', 'notes', 'is_liked'];

    public function ingredients() {
        return $this->hasMany(Ingredient::class);
    }

    public function mealType() {
        return $this->belongsTo(MealType::class);
    }

    public function saveIngredients($items) {
        Ingredient::query()->where('meal_id', $this->id)->delete();
        foreach ($items as $item) {
            if (isset($item['product_id']) && $item['product_id'] !== "new::{$item['name']}") {
                $product = Product::find($item['id']);
            } else if (isset($item['name'])) {
                $product = Product::create([
                    'name' => $item['name'],
                    'team_id' => $this->team_id,
                    'user_id' => $this->user_id
                ]);
            }
            $this->ingredients()->create([
                "team_id" => $this->team_id,
                "user_id" => $this->user_id,
                "meal_id" => $this->id,
                "product_id" => $product->id,
                "quantity" => $item['quantity'],
                "name" => $item['name'],
                "unit" => $item['unit']
            ]);
        }
    }
}

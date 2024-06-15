<?php

namespace App\Domains\Meal\Models;

use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Meal extends Model
{
    use HasFactory;

    protected $fillable = ['team_id', 'user_id', 'name', 'meal_type_id', 'menu_id', 'notes', 'is_liked'];

    protected $with = ['mealType'];

    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }

    public function mealType()
    {
        return $this->belongsTo(MealType::class);
    }

    public function saveIngredients($items)
    {
        Ingredient::query()->where('meal_id', $this->id)->delete();
        foreach ($items as $item) {
            if (isset($item['product_id']) && !str_contains($item['product_id'], "new::")) {
                $product = Product::find($item['product_id']);
            } else {
                $ingredientName = str_replace("new::", "", $item['product_id']);
                $product = Product::firstOrCreate([
                    'name' => $ingredientName,
                    'team_id' => $this->team_id,
                    'user_id' => $this->user_id,
                ]);
            }
            $this->ingredients()->create([
                'team_id' => $this->team_id,
                'user_id' => $this->user_id,
                'meal_id' => $this->id,
                'product_id' => $product->id,
                'quantity' => $item['quantity'] ?? 1,
                'name' => $product->name,
                'unit' => $item['unit'] ?? "",
            ]);
        }
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'notes' => $this->notes,
            'prep_minutes' => $this->prep_minutes,
            'cook_minutes' => $this->cook_minutes,
            'servings' => $this->servings,
            'is_liked' => $this->is_liked,
            'meal_type_id' => $this->meal_type_id,
            'meal_type' => $this->mealType?->name,
            'menu_id' => $this->menu_id,
            'menu' => $this->menu?->name,
            'ingredients' => IngredientResource::collection($this->whenLoaded('ingredients')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

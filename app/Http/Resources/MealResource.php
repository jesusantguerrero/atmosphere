<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MealResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "notes" =>$this->notes,
            "meal_type_id" => $this->meal_type_id,
            "meal_type" => $this->mealType?->name,
            "menu_id" => $this->menu_id,
            "menu" => $this->menu?->name,
            "ingredients" => IngredientResource::collection($this->whenLoaded('ingredients')),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}

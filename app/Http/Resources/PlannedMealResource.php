<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlannedMealResource extends JsonResource
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
            'id' => $this->id,
            'mealId' => $this->dateable->id,
            'name' => $this->dateable->name,
            'mealTypeName' => $this->dateable->mealType?->name,
            'is_liked' => $this->is_liked,
            'is_meal_liked' => $this->dateable->is_liked,
            'mealTypeId' => $this->dateable->mealType?->id,
            'date' => $this->date,
        ];
    }
}

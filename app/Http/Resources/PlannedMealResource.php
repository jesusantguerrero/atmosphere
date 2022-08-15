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
            'date' => $this->date,
        ];
    }
}

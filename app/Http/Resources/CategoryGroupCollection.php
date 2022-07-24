<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryGroupCollection extends JsonResource
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
            'created_at' => $this->created_at,
            'depth' => $this->depth,
            'description' => $this->description,
            'display_id' => $this->display_id,
            'id' => $this->id,
            'index' => $this->index,
            'name' => $this->name,
            'parent_id' => null,
            'resource_type' => "transactions",
            'resource_type_id' => $this->resource_type_id,
            'status' => $this->status,
            'subCategories' => CategoryCollection::collection($this->whenLoaded('subCategories')),
        ];
    }
}

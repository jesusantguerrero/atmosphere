<?php

namespace App\Http\Resources;

use App\Helpers\RequestQueryHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [ $startDate ] = RequestQueryHelper::getFilterDates($filters);

        $month = $startDate ?? date('Y-m-01');
        $normalArray = parent::toArray($request);
        return array_merge($this->getBudgetInfo($month), $normalArray, [ 'month' => $month ]);
    }
}

<?php

namespace App\Http\Resources;

use App\Helpers\RequestQueryHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Budget\Services\BudgetCategoryService;

class CategoryCollection extends JsonResource
{
    public $service;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->service = new BudgetCategoryService();
    }

    public function toArray($request)
    {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate] = RequestQueryHelper::getFilterDates($filters);

        $month = $startDate ?? date('Y-m-01');
        $normalArray = parent::toArray($request);

        return [
            ...$normalArray,
            ...$this->service->getBudgetInfo($this->resource, $month),
            'month' => $month
        ];
    }
}

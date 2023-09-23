<?php

namespace App\Http\Resources;

use App\Domains\Budget\Services\BudgetCategoryService;
use App\Helpers\RequestQueryHelper;
use Illuminate\Http\Resources\Json\JsonResource;

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

        // if ($this->id == 723) {
        //     dd( $this->service->getBudgetInfo($this->resource, $month), $this->id);
        // }
        return array_merge(
            $normalArray,
            [
                'month' => $month,
            ],
            $this->service->getBudgetInfo($this->resource, $month));
    }
}

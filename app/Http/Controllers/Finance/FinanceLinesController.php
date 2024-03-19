<?php

namespace App\Http\Controllers\Finance;

use Inertia\Inertia;
use App\Models\Setting;
use Illuminate\Http\Request;
use Freesgen\Atmosphere\Http\Querify;
use Insane\Journal\Models\Core\Category;
use Freesgen\Atmosphere\Http\InertiaController;
use App\Domains\Transaction\Services\ReportService;
use App\Domains\Transaction\Services\TransactionService;

class FinanceLinesController extends InertiaController
{
    use Querify;

    const DateFormat = 'Y-m-d';

    public function __construct(Category $category)
    {
        $this->model = $category;
        $this->templates = [
            'index' => 'Finance/Category',
            'show' => 'Finance/Category',
        ];
        $this->searchable = ['id', 'date'];
        $this->includes = [
            'transactions',
        ];
        $this->appends = [];
    }

    public function index(Request $request)
    {
        $queryParams = request()->query();
        $settings = Setting::getByTeam(auth()->user()->current_team_id);
        $timeZone = $settings['team_timezone'] ?? config('app.timezone');

        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters, $timeZone);


        $category = Category::find($filters['category_id'] ?? $filters['group_id']);
        $splits = TransactionService::getSplits(
            auth()->user()->current_team_id,
            [
                'categoryId' => $category->parent_id ? $category->id : null,
                'groupId' => ! $category->parent_id ? $category->id : null,
                'limit' => 50,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]
        );

        return inertia($this->templates['show'], [
            'sectionTitle' => $category?->name,
            'accountId' => $category?->id,
            'resource' => $category,
            'transactions' => $splits,
            'serverSearchOptions' => $this->getServerParams(),
        ]);
    }

    public function show(Category $category)
    {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        return Inertia::render($this->templates['show'], [
            'sectionTitle' => $category->name,
            'accountId' => $category->id,
            'resource' => $category,
            'transactions' => TransactionService::getSplits($category->team_id,
                [
                    'categoryId' => $category->id,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ]
            ),
        ]);
    }

    public function getTransactions(Category $category)
    {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        return [
            'sectionTitle' => $category->name,
            'accountId' => $category->id,
            'resource' => $category,
            'transactions' => ReportService::getExpensesByCategoriesInPeriod(
                $category->team_id,
                $startDate,
                $endDate,
                [$category->id]
            )->groupBy('date')->map(function ($monthItems) {
                return [
                    'date' => $monthItems->first()->date,
                    'total' => $monthItems->sum('total'),
                ];
            })->sortBy('date'),
        ];
    }

    public function getDetails(Category $category)
    {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);
        $groupId = ! $category->parent_id ? $category->id : null;

        return [
            'sectionTitle' => $category->name,
            'categoryId' => $category->id,
            'resource' => $category,
            'transactions' => TransactionService::getCategoryExpenseDetails($category->team_id, $startDate, $endDate, 50, $category, $groupId),
        ];
    }
}

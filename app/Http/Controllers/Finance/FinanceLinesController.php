<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Transaction\Services\TransactionService;
use Freesgen\Atmosphere\Http\InertiaController;
use Freesgen\Atmosphere\Http\Querify;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;

class FinanceLinesController extends InertiaController {
    use Querify;
    const DateFormat = 'Y-m-d';

    public function __construct(Account $account)
    {
        $this->model = $account;
        $this->templates = [
            'index' => 'Finance/Category',
            'show' => 'Finance/Category'
        ];
        $this->searchable = ["id", "date"];
        $this->includes = [
            'transactions'
        ];
        $this->appends = [];
    }

    public function index(Request $request) {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);
        $categoryId = request()->query('category_id') ?? null;
        $groupId = request()->query('group_id') ?? null;


        $category = Category::find($categoryId || $groupId);
        $splits = TransactionService::getSplits(auth()->user()->current_team_id, [
            "categoryId" => $categoryId,
            "groupId" => $groupId,
            "limit" => 25,
            "startDate" => $startDate,
            "endDate" => $endDate
        ]);

        return inertia($this->templates['show'], [
            "sectionTitle" => $category?->name,
            'accountId' => $category?->id,
            'resource' => $category,
            'transactions' => $splits
        ]);
    }

    public function show(Category $category) {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        return Inertia::render($this->templates['show'], [
            "sectionTitle" => $category->name,
            'accountId' => $category->id,
            'resource' => $category,
            'transactions' => TransactionService::getSplits($category->team_id,
                [
                    "categoryId" => $category->id,
                    "startDate" => $startDate,
                    "endDate" => $endDate
                ]
            )
        ]);
    }
}

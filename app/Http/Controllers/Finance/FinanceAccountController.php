<?php

namespace App\Http\Controllers\Finance;

use Freesgen\Atmosphere\Http\InertiaController;
use Freesgen\Atmosphere\Http\Querify;
use Inertia\Inertia;
use Insane\Journal\Models\Core\Account;

class FinanceAccountController extends InertiaController {
    use Querify;
    const DateFormat = 'Y-m-d';

    public function __construct(Account $account)
    {
        $this->model = $account;
        $this->templates = [
            'index' => 'Finance/Account',
            'show' => 'Finance/Account'
        ];
        $this->searchable = ["id", "date"];
        $this->includes = [
            'transactions'
        ];
        $this->appends = [];
    }

    public function show(Account $account) {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        return Inertia::render($this->templates['show'], [
            "sectionTitle" => $account->name,
            'accountId' => $account->id,
            'resource' => $account,
            'transactions' => $account->transactionSplits(25, $startDate, $endDate)
        ]);
    }
}

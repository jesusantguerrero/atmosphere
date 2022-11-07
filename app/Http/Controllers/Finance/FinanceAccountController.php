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
            'transactions',
            'transactionLines'
        ];
        $this->appends = [];
    }

    public function show(Account $account) {
        return Inertia::render($this->templates['show'], [
            "sectionTitle" => $account->name,
            'accountId' => $account->id,
            'resource' => $account,
            'transactions' => $account->transactionLines
        ]);
    }
}

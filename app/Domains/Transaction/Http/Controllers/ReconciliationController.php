<?php

namespace App\Domains\Transaction\Http\Controllers;

use App\Domains\Transaction\Data\ReconciliationParamsData;
use App\Domains\Transaction\Services\ReconciliationService;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasEnrichedRequest;
use Insane\Journal\Models\Accounting\Reconciliation;
use Insane\Journal\Models\Core\Account;

class ReconciliationController extends Controller {
    const DateFormat = 'Y-m-d';

    use HasEnrichedRequest;

    public function create(Account $account) {
        [$startDate, $endDate] = $this->getFilterDates();

        return inertia("Finance/Reconciliation/Create", [
            "account" => $account,
            'transactions' => $account->transactionSplits(0, $startDate, $endDate),
            "dates" => [$startDate, $endDate]
        ]);
    }

    public function show(Reconciliation $reconciliation) {
        return inertia("Finance/Reconciliation/Show", [
            "account" => $reconciliation->account,
            'transactions' => $reconciliation->getTransactions(),
            'reconciliation' => $reconciliation,
            "dates" => [null, $reconciliation->date]
        ]);
    }

    public function store(Account $account, ReconciliationService $service) {

        $reconciliation = $service->create($account, 
           ReconciliationParamsData::from([
                ...$this->getPostData(),
                "account_id" => $account->id,
                "user_id" => auth()->user()->id
            ])
       );

       if ($reconciliation->difference) {
            return redirect("/finance/reconciliation/$reconciliation->id");
       }
   }
}

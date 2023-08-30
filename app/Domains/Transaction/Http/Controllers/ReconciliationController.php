<?php

namespace App\Domains\Transaction\Http\Controllers;

use App\Domains\Transaction\Data\ReconciliationParamsData;
use App\Domains\Transaction\Services\ReconciliationService;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasEnrichedRequest;
use Illuminate\Support\Facades\Gate;
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

   public function adjustment(Reconciliation $reconciliation, ReconciliationService $service) {
        if(!Gate::forUser(auth()->user())->check('adjust', $reconciliation)) {
            back()->with('flash', [
                'banner' => "Can't reconcile this account"
            ]);
        }
        $service->saveAdjustment($reconciliation);
   }

   public function update(Reconciliation $reconciliation, ReconciliationService $service) {
        $reconciliation = $service->update($reconciliation, ReconciliationParamsData::from([
                ...$this->getPostData(),
                "account_id" => $reconciliation->account_id,
                "user_id" => auth()->user()->id
            ])
        );

        if ($reconciliation->difference) {
            return back()->with('flash', [
                'banner' => "Can't reconcile this account"
            ]);
        } else {
            back()->with('flash', [
                'banner' => "Updated correctly"
            ]);
        }
    }
}

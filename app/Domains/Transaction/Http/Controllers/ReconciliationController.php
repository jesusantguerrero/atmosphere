<?php

namespace App\Domains\Transaction\Http\Controllers;

use App\Domains\Transaction\Data\ReconciliationParamsData;
use App\Domains\Transaction\Services\ReconciliationService;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasEnrichedRequest;
use Exception;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Models\Accounting\Reconciliation;
use Insane\Journal\Models\Accounting\ReconciliationEntry;
use Insane\Journal\Models\Core\Account;

class ReconciliationController extends Controller {
    const DateFormat = 'Y-m-d';

    use HasEnrichedRequest;

    public function accountReconciliations(Account $account, ReconciliationService $service) {
        [$startDate, $endDate] = $this->getFilterDates();

        return inertia("Finance/Reconciliation/AccountReconciliations", [
            "account" => $account,
            'transactions' => $service->listHistoryOf($account),
            "dates" => [$startDate, $endDate]
        ]);
    }
    public function create(Account $account) {
        [$startDate, $endDate] = $this->getFilterDates();

        return inertia("Finance/Reconciliation/Create", [
            "account" => $account,
            'transactions' => $account->transactionSplits(0, $startDate, $endDate),
            "dates" => [$startDate, $endDate]
        ]);
    }

    public function show(Reconciliation $reconciliation) {
        $page = request()->get('page') ?? 1;
        return inertia("Finance/Reconciliation/Show", [
            "account" => $reconciliation->account,
            'transactions' => $reconciliation->getTransactions(25, $page),
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

   public function syncTransactions(Reconciliation $reconciliation, ReconciliationService $service) {
        $reconciliation = $service->syncTransactions($reconciliation);

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

   public function delete(Reconciliation $reconciliation, ReconciliationService $service) {
      try {
            $accountId = $reconciliation->account_id;
            $service->delete($reconciliation);
            return redirect("/finance/reconciliation/$accountId")->with('flash', [
                'banner' => "Can't reconcile this account"
            ]);
        } catch (Exception) {
            back()->with('flash', [
                'banner' => "Updated correctly"
            ]);
        }
    }


   public function checkReconciliationEntry(Reconciliation $reconciliation, ReconciliationEntry $reconciliationEntry, ReconciliationService $service) {
    if(!Gate::forUser(auth()->user())->check('adjust', $reconciliation)) {
        back()->with('flash', [
            'banner' => "Can't reconcile this account"
        ]);
    }
    $postData = $this->getPostData();
    $service->checkLine($reconciliation, $reconciliationEntry, $postData['matched']);
   }
}

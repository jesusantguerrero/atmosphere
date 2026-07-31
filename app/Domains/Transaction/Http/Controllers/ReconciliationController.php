<?php

namespace App\Domains\Transaction\Http\Controllers;

use App\Domains\Transaction\Data\ReconciliationParamsData;
use App\Domains\Transaction\Services\ReconciliationService;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasEnrichedRequest;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Models\Accounting\Reconciliation;
use Insane\Journal\Models\Accounting\ReconciliationEntry;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;

class ReconciliationController extends Controller
{
    const DateFormat = 'Y-m-d';

    use HasEnrichedRequest;

    public function accountReconciliations(Account $account, ReconciliationService $service)
    {
        [$startDate, $endDate] = $this->getFilterDates();

        $reconciliations = $service->listHistoryOf($account);
        $lastReconciliation = $reconciliations->last();
        $unreconciledTransactions = $lastReconciliation
            ? $account->transactionsToReconcile(null, $lastReconciliation->date)
            : collect();

        // Get recent unreconciled transactions with full details for preview
        $lastDate = $lastReconciliation?->date ?? $startDate;
        $previewTransactions = $account->transactionSplits(10, $lastDate, $endDate);

        return inertia('Finance/Reconciliation/AccountReconciliations', [
            'account' => $account,
            'lastReconciliation' => $lastReconciliation,
            'reconciliations' => $reconciliations,
            'transactions' => $unreconciledTransactions,
            'previewTransactions' => $previewTransactions,
            'dates' => [$startDate, $endDate],
        ]);
    }

    public function create(Account $account)
    {
        [$startDate, $endDate] = $this->getFilterDates();

        return inertia('Finance/Reconciliation/Create', [
            'account' => $account,
            'transactions' => $account->transactionSplits(0, $startDate, $endDate),
            'dates' => [$startDate, $endDate],
        ]);
    }

    public function show(Reconciliation $reconciliation, ReconciliationService $service)
    {
        return inertia('Finance/Reconciliation/Show', [
            'account' => $reconciliation->account,
            'transactions' => $this->getReconciliationTransactions($reconciliation),
            'matchedCount' => ReconciliationEntry::where('reconciliation_id', $reconciliation->id)
                ->where('matched', true)
                ->count(),
            'totalEntries' => ReconciliationEntry::where('reconciliation_id', $reconciliation->id)->count(),
            'ledgerBalance' => $service->balanceAsOf($reconciliation->account, $reconciliation->date),
            'reconciliation' => $reconciliation,
            'dates' => [null, $reconciliation->date],
        ]);
    }

    /**
     * Loger balance as of a date — powers the live preview in the
     * "start reconciliation" modal when picking a statement date.
     */
    public function balanceAt(Account $account, ReconciliationService $service)
    {
        $date = request()->get('date') ?? date('Y-m-d');

        return response()->json([
            'date' => $date,
            'balance' => $service->balanceAsOf($account, $date),
        ]);
    }

    /**
     * Paginated transactions of the reconciliation, optionally filtered by
     * match status (?matched=pending|matched). Mirrors the vendor
     * Reconciliation::getTransactions() query, which cannot filter.
     */
    private function getReconciliationTransactions(Reconciliation $reconciliation)
    {
        $filter = request()->get('matched');

        $query = Transaction::whereHas('lines', function ($query) use ($reconciliation) {
            $query->where('account_id', $reconciliation->account_id);
        })
        ->join('reconciliation_entries', fn ($q) => $q->on('transactions.id', 'reconciliation_entries.transaction_id')
            ->where('reconciliation_id', $reconciliation->id))
        ->with(['splits', 'payee', 'category', 'splits.payee', 'account', 'counterAccount'])
        ->select()
        ->addSelect(DB::raw('reconciliation_entries.id as entry_id, reconciliation_entries.matched is_matched'))
        ->orderByDesc('date');

        if ($filter === 'pending') {
            $query->where('reconciliation_entries.matched', false);
        } elseif ($filter === 'matched') {
            $query->where('reconciliation_entries.matched', true);
        }

        return $query->paginate(25)->withQueryString();
    }


    public function store(Account $account, ReconciliationService $service)
    {
        $reconciliation = $service->create($account,
            ReconciliationParamsData::from([
                ...$this->getPostData(),
                'account_id' => $account->id,
                'user_id' => auth()->user()->id,
            ])
        );

        if ($reconciliation->difference) {
            return redirect("/finance/reconciliation/$reconciliation->id");
        }
    }

    public function adjustment(Reconciliation $reconciliation, ReconciliationService $service)
    {
        if (! Gate::forUser(auth()->user())->check('adjust', $reconciliation)) {
            back()->with('flash', [
                'banner' => "Can't reconcile this account",
            ]);
        }
        $service->saveAdjustment($reconciliation);
    }

    public function update(Reconciliation $reconciliation, ReconciliationService $service)
    {
        $reconciliation = $service->update($reconciliation, ReconciliationParamsData::from([
            ...$this->getPostData(),
            'account_id' => $reconciliation->account_id,
            'user_id' => auth()->user()->id,
        ])
        );

        if ($reconciliation->difference) {
            return back()->with('flash', [
                'banner' => "Can't reconcile this account",
            ]);
        } else {
            back()->with('flash', [
                'banner' => 'Updated correctly',
            ]);
        }
    }

    public function syncTransactions(Reconciliation $reconciliation, ReconciliationService $service)
    {
        $reconciliation = $service->syncTransactions($reconciliation);

        if ($reconciliation->difference) {
            return back()->with('flash', [
                'banner' => "Can't reconcile this account",
            ]);
        } else {
            back()->with('flash', [
                'banner' => 'Updated correctly',
            ]);
        }
    }

    public function delete(Reconciliation $reconciliation, ReconciliationService $service)
    {
        try {
            $accountId = $reconciliation->account_id;
            $service->delete($reconciliation);

            return redirect("/finance/reconciliation/$accountId")->with('flash', [
                'banner' => "Can't reconcile this account",
            ]);
        } catch (Exception) {
            back()->with('flash', [
                'banner' => 'Updated correctly',
            ]);
        }
    }

    public function checkReconciliationEntry(Reconciliation $reconciliation, ReconciliationEntry $reconciliationEntry, ReconciliationService $service)
    {
        if (! Gate::forUser(auth()->user())->check('adjust', $reconciliation)) {
            back()->with('flash', [
                'banner' => "Can't reconcile this account",
            ]);
        }
        $postData = $this->getPostData();
        $service->checkLine($reconciliation, $reconciliationEntry, $postData['matched']);
    }
}

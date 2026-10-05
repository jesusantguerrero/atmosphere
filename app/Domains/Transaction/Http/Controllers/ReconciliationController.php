<?php

namespace App\Domains\Transaction\Http\Controllers;

use App\Domains\Transaction\Data\ReconciliationParamsData;
use App\Domains\Transaction\Services\ReconciliationService;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasEnrichedRequest;
use App\Http\Requests\ReconciliationEntryRequest;
use App\Http\Requests\ReconciliationRequest;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use Insane\Journal\Models\Accounting\Reconciliation;
use Insane\Journal\Models\Accounting\ReconciliationEntry;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Transaction;

class ReconciliationController extends Controller
{
    const DateFormat = 'Y-m-d';

    use HasEnrichedRequest;

    public function accountReconciliations(Account $account, ReconciliationService $service): Response
    {
        $this->authorize('update', $account);

        [$startDate, $endDate] = $this->getFilterDates();

        $reconciliations = $service->listHistoryOf($account);
        $lastReconciliation = $reconciliations->first();
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
        $this->authorize('update', $account);

        [$startDate, $endDate] = $this->getFilterDates();

        return inertia('Finance/Reconciliation/Create', [
            'account' => $account,
            'transactions' => $account->transactionSplits(0, $startDate, $endDate),
            'dates' => [$startDate, $endDate],
        ]);
    }

    /**
     * Cross-account reconciliation hub. One row per account with its latest
     * reconciliation (date + status + leftover difference) and how many verified
     * movements are still unreconciled, sorted MOST-STALE FIRST (never-reconciled
     * on top, then longest since last reconciled) so the accounts that most need
     * attention surface without hunting account by account.
     */
    public function hub(Request $request, ReconciliationService $service): Response
    {
        $teamId = $request->user()->current_team_id;
        $today = now()->format('Y-m-d');

        $accounts = Account::getByDetailTypes($teamId, AccountDetailType::ALL)->load('detailType');
        $accountIds = $accounts->pluck('id')->all();
        $pendingDifferences = $service->pendingDifferences($accounts
            ->pluck('reconciliationLast')
            ->filter(fn ($reconciliation) => $reconciliation?->status === Reconciliation::STATUS_PENDING)
            ->pluck('id')->all());

        // One grouped COUNT instead of pulling every unreconciled line per account
        // (a months-behind card can have hundreds). Mirrors
        // Account::transactionsToReconcile: verified lines up to today, not yet
        // attached to any reconciliation entry.
        $unreconciled = DB::table('transaction_lines')
            ->join('transactions', 'transactions.id', 'transaction_lines.transaction_id')
            ->leftJoin('reconciliation_entries', 'reconciliation_entries.transaction_line_id', 'transaction_lines.id')
            ->where('transactions.status', Transaction::STATUS_VERIFIED)
            ->whereNull('reconciliation_entries.id')
            ->where('transactions.date', '<=', $today)
            ->whereIn('transaction_lines.account_id', $accountIds)
            ->groupBy('transaction_lines.account_id')
            ->selectRaw('transaction_lines.account_id as account_id, COUNT(*) as cnt')
            ->pluck('cnt', 'account_id');

        $rows = $accounts->map(function ($account) use ($unreconciled, $pendingDifferences) {
            $last = $account->reconciliationLast;
            $lastDate = $last?->date ? Carbon::parse($last->date) : null;

            return [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->detailType?->name,
                'balance' => (float) $account->balance,
                'currency_code' => $account->currency_code,
                'last_id' => $last?->id,
                'last_date' => $lastDate?->format('Y-m-d'),
                'last_status' => $last?->status,
                'last_difference' => $last ? $pendingDifferences->get($last->id, (float) $last->difference) : null,
                'days_since' => $lastDate ? (int) $lastDate->startOfDay()->diffInDays(now()->startOfDay()) : null,
                'unreconciled_count' => (int) ($unreconciled[$account->id] ?? 0),
            ];
        })
            ->sortByDesc(fn ($r) => $r['days_since'] === null ? PHP_INT_MAX : $r['days_since'])
            ->values();

        return inertia('Finance/Reconciliation/Hub', [
            'accounts' => $rows,
            'sectionTitle' => 'Reconciliation',
        ]);
    }

    public function show(Reconciliation $reconciliation, ReconciliationService $service)
    {
        $this->authorize('adjust', $reconciliation);

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
        $this->authorize('update', $account);

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

        if ($search = request()->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transactions.description', 'like', "%{$search}%")
                    ->orWhere('transactions.total', 'like', "%{$search}%")
                    ->orWhereHas('payee', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->paginate(25)->withQueryString();
    }

    public function store(Account $account, ReconciliationService $service, ReconciliationRequest $request): RedirectResponse
    {
        $this->authorize('update', $account);

        $reconciliation = $service->create($account,
            ReconciliationParamsData::from([
                ...$request->validated(),
                'account_id' => $account->id,
                'user_id' => auth()->user()->id,
            ])
        );

        return redirect("/finance/reconciliation/$reconciliation->id");
    }

    public function adjustment(Reconciliation $reconciliation, ReconciliationService $service, ReconciliationRequest $request): RedirectResponse
    {
        $this->authorize('adjust', $reconciliation);

        $service->saveAdjustment($reconciliation, ReconciliationParamsData::from([
            ...$request->validated(),
            'account_id' => $reconciliation->account_id,
            'user_id' => auth()->user()->id,
        ]));

        return redirect("/finance/reconciliation/{$reconciliation->id}");
    }

    public function update(Reconciliation $reconciliation, ReconciliationService $service, ReconciliationRequest $request): RedirectResponse
    {
        $this->authorize('adjust', $reconciliation);

        $reconciliation = $service->update($reconciliation, ReconciliationParamsData::from([
            ...$request->validated(),
            'account_id' => $reconciliation->account_id,
            'user_id' => auth()->user()->id,
        ])
        );

        if ($reconciliation->difference) {
            return back()->with('flash', [
                'banner' => "Can't reconcile this account",
            ]);
        } else {
            return back()->with('flash', [
                'banner' => 'Updated correctly',
            ]);
        }
    }

    public function syncTransactions(Reconciliation $reconciliation, ReconciliationService $service): RedirectResponse
    {
        $this->authorize('adjust', $reconciliation);

        $reconciliation = $service->syncTransactions($reconciliation);

        if ($reconciliation->difference) {
            return back()->with('flash', [
                'banner' => "Can't reconcile this account",
            ]);
        } else {
            return back()->with('flash', [
                'banner' => 'Updated correctly',
            ]);
        }
    }

    public function delete(Reconciliation $reconciliation, ReconciliationService $service): RedirectResponse
    {
        $this->authorize('adjust', $reconciliation);

        try {
            $accountId = $reconciliation->account_id;
            $service->delete($reconciliation);

            return redirect("/finance/accounts/$accountId/reconciliations")->with('flash', [
                'banner' => 'Deleted correctly',
            ]);
        } catch (Exception) {
            return back()->with('flash', [
                'banner' => "Can't delete this reconciliation",
            ]);
        }
    }

    public function checkReconciliationEntry(Reconciliation $reconciliation, ReconciliationEntry $reconciliationEntry, ReconciliationService $service, ReconciliationEntryRequest $request): RedirectResponse
    {
        $this->authorize('adjust', $reconciliation);
        abort_unless((int) $reconciliationEntry->reconciliation_id === (int) $reconciliation->id, 404);
        $postData = $request->validated();
        $service->checkLine($reconciliation, $reconciliationEntry, (bool) $postData['matched']);

        return back();
    }
}

<?php

namespace App\Domains\Transaction\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Transaction\Data\ReconciliationParamsData;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Accounting\Reconciliation;
use Insane\Journal\Models\Accounting\ReconciliationEntry;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction as CoreTransaction;

class ReconciliationService
{
    /**
     * @param  array<int, int>  $reconciliationIds
     * @return Collection<int, float>
     */
    public function pendingDifferences(array $reconciliationIds): Collection
    {
        if ($reconciliationIds === []) {
            return collect();
        }

        return Reconciliation::query()
            ->whereKey($reconciliationIds)
            ->where('status', Reconciliation::STATUS_PENDING)
            ->select(['id', 'amount'])
            ->addSelect(['ledger_balance' => TransactionLine::query()
                ->selectRaw('COALESCE(SUM(transaction_lines.amount * transaction_lines.type), 0)')
                ->join('transactions', 'transactions.id', 'transaction_lines.transaction_id')
                ->where('transactions.status', CoreTransaction::STATUS_VERIFIED)
                ->whereColumn('transaction_lines.account_id', 'reconciliations.account_id')
                ->whereColumn('transaction_lines.date', '<=', 'reconciliations.date'),
            ])
            ->get()
            ->mapWithKeys(fn (Reconciliation $reconciliation): array => [
                $reconciliation->id => (float) $reconciliation->ledger_balance - (float) $reconciliation->amount,
            ]);
    }

    /**
     * Account balance as of a given date (verified lines only). Reconciling
     * against the CURRENT balance made any reconciliation dated in the past —
     * a statement from two months ago, say — report a meaningless difference.
     */
    public function balanceAsOf(Account $account, string $date): float
    {
        return (float) $account->transactionLines()
            ->join('transactions', 'transactions.id', 'transaction_lines.transaction_id')
            ->where('transactions.status', CoreTransaction::STATUS_VERIFIED)
            ->where('transaction_lines.date', '<=', $date)
            ->sum(DB::raw('amount * type'));
    }

    public function listHistoryOf(Account $account)
    {
        return Reconciliation::where([
            'team_id' => $account->team_id,
            'account_id' => $account->id,
        ])
            ->addSelect([
                'total_transactions' => ReconciliationEntry::selectRaw('COUNT(id)')->whereColumn('reconciliation_id', 'reconciliations.id'),
                'matched_transactions' => ReconciliationEntry::selectRaw('COUNT(id)')->whereColumn('reconciliation_id', 'reconciliations.id')->where('matched', true),
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
    }

    public function create(Account $account, ReconciliationParamsData $params)
    {
        $transactions = $account->transactionsToReconcile(null, $params->date);

        if ($dateReconciliation = $this->getByDate($account->team_id, $account->id, $params->date)) {
            $this->update($dateReconciliation, $params);

            return $dateReconciliation;
        }

        $diff = $this->balanceAsOf($account, $params->date) - $params->balance;
        $reconciliation = Reconciliation::create([
            'user_id' => $params->user_id,
            'team_id' => $account->team_id,
            'account_id' => $account->id,
            'date' => $params->date,
            'amount' => $params->balance,
            'difference' => $diff,
            'status' => $diff ? Reconciliation::STATUS_PENDING : Reconciliation::STATUS_COMPLETED,
        ]);

        $reconciliation->createEntries($transactions->toArray());

        return $reconciliation;
    }

    public function update(Reconciliation $reconciliation, ReconciliationParamsData $params): Reconciliation
    {
        $extraTransactions = $reconciliation->account->transactionsToReconcile(null, $reconciliation->date);
        $diff = $this->balanceAsOf($reconciliation->account, $reconciliation->date) - $params->balance;

        $reconciliation->update([
            'amount' => $params->balance,
            'difference' => $diff,
            'status' => $diff ? Reconciliation::STATUS_PENDING : Reconciliation::STATUS_COMPLETED,
        ]);

        if (count($extraTransactions)) {
            $reconciliation->addEntries($extraTransactions->toArray());
        }

        if ($reconciliation->status === Reconciliation::STATUS_COMPLETED) {
            $reconciliation->checkStatus();
        }

        return $reconciliation;
    }

    public function delete(Reconciliation $reconciliation)
    {
        $entries = $reconciliation->entries()->select(['id', 'transaction_line_id'])->get();

        TransactionLine::whereIn('id', $entries->pluck('transaction_line_id'))
            ->update([
                'matched' => false,
            ]);

        $reconciliation->entries()->whereIn('id', $entries->pluck('id'))->delete();

        $reconciliation->delete();

        return $reconciliation;
    }

    /**
     * Book a "plug" transaction equal to the leftover difference so the account
     * ledger agrees with the statement, then close the reconciliation. This is
     * the escape hatch for a gap the user can't track down line by line.
     *
     * The difference is recomputed from the balance the user is currently
     * looking at ($params->balance) so the plug always matches the on-screen
     * number, never a stale stored value. The plug is dated at the statement
     * date (not today) so balanceAsOf() genuinely reconciles and the next
     * month's reconciliation builds on a correct balance.
     *
     * Completion goes through syncTransactions()/addEntries() — NOT the vendor
     * Reconciliation::addEntry(), which references an undefined $items and never
     * recomputes the difference, so completing through it was unreliable.
     */
    public function saveAdjustment(Reconciliation $reconciliation, ReconciliationParamsData $params): Reconciliation
    {
        return DB::transaction(function () use ($reconciliation, $params) {
            $diff = $this->balanceAsOf($reconciliation->account, $reconciliation->date) - $params->balance;

            if (abs($diff) >= 0.005) {
                Transaction::createTransaction([
                    'team_id' => $reconciliation->team_id,
                    'user_id' => $reconciliation->user_id,
                    'account_id' => $reconciliation->account_id,
                    'payee_id' => 'new',
                    'payee_label' => 'Loger adjustment',
                    'date' => Carbon::parse($reconciliation->date)->format('Y-m-d'),
                    'currency_code' => $reconciliation->account->currency_code,
                    'category_id' => Category::findOrCreateByName($reconciliation, BudgetReservedNames::READY_TO_ASSIGN->value),
                    'description' => 'Loger adjustment',
                    'direction' => $diff > 0 ? Transaction::DIRECTION_CREDIT : Transaction::DIRECTION_DEBIT,
                    'status' => Transaction::STATUS_VERIFIED,
                    'total' => abs($diff),
                    'items' => [],
                ]);
            }

            // Record the (now matched) statement balance and close it out. Rebuild
            // entries from the current unreconciled lines — which now include the
            // plug — with status already completed, so every entry/line is marked
            // matched by checkStatus().
            $reconciliation->update([
                'amount' => $params->balance,
                'difference' => 0,
                'status' => Reconciliation::STATUS_COMPLETED,
            ]);

            $this->syncTransactions($reconciliation);

            return $reconciliation;
        });
    }

    public function checkLine(Reconciliation $reconciliation, ReconciliationEntry $line, bool $matched = false)
    {
        $reconciliation->entries()->where('id', $line->id)->update([
            'matched' => $matched,
        ]);

        return $reconciliation;
    }

    public function syncTransactions(Reconciliation $reconciliation): Reconciliation
    {
        $extraTransactions = $reconciliation->account->transactionsToReconcile(null, $reconciliation->date);
        $reconciliation->addEntries($extraTransactions->toArray());
        $reconciliation->update([
            'difference' => $this->balanceAsOf($reconciliation->account, $reconciliation->date) - (float) $reconciliation->amount,
        ]);

        if ($reconciliation->status === Reconciliation::STATUS_COMPLETED) {
            $reconciliation->checkStatus();
        }

        return $reconciliation;
    }

    public function checkOpenReconciliation(Account $account, Transaction|CoreTransaction $transaction)
    {
        $reconciliation = Reconciliation::where([
            'account_id' => $account->id,
            'status' => Reconciliation::STATUS_PENDING,
        ])
            ->where('date', '>=', $transaction->date)
            ->first();

        if (! $reconciliation) {
            return;
        }

        return $this->syncTransactions($reconciliation);
    }

    public function getByDate($teamId, $accountId, $date)
    {
        return Reconciliation::where([
            'team_id' => $teamId,
            'account_id' => $accountId,
            'date' => $date,
        ])
            ->first();
    }
}

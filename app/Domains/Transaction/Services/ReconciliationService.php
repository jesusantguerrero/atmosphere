<?php

namespace App\Domains\Transaction\Services;

use Exception;
use Insane\Journal\Models\Core\Account;
use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Transaction\Models\TransactionLine;
use Insane\Journal\Models\Accounting\Reconciliation;
use Insane\Journal\Models\Accounting\ReconciliationEntry;
use App\Domains\Transaction\Data\ReconciliationParamsData;
use Insane\Journal\Models\Core\Transaction as CoreTransaction;

class ReconciliationService
{
    public function listHistoryOf(Account $account)
    {
        return Reconciliation::where([
            'team_id' => $account->team_id,
            'account_id' => $account->id,
        ])
        ->addSelect([
            'total_transactions' => ReconciliationEntry::selectRaw('COUNT(id)')->whereColumn('reconciliation_id', 'reconciliations.id'),
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

        $diff = $account->balance - $params->balance;
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

    public function update(Reconciliation $reconciliation, ReconciliationParamsData $params)
    {
        $extraTransactions = $reconciliation->account->transactionsToReconcile(null, $reconciliation->date);
        $diff = $reconciliation->account->balance - $params->balance;

        $reconciliation->update([
            'amount' => $params->balance,
            'difference' => $diff,
            'status' => $diff ? Reconciliation::STATUS_PENDING : Reconciliation::STATUS_COMPLETED,
        ]);

        if (count($extraTransactions)) {
            $reconciliation->addEntries($extraTransactions->toArray());
        }

        $reconciliation->checkStatus();

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

    public function saveAdjustment(Reconciliation $reconciliation)
    {
        $transaction = Transaction::createTransaction([
            'team_id' => $reconciliation->team_id,
            'user_id' => $reconciliation->user_id,
            'account_id' => $reconciliation->account_id,
            'payee_id' => 'new',
            'payee_label' => 'Loger adjustment',
            'date' => now()->format('Y-m-d'),
            'currency_code' => $reconciliation->account->currency_code,
            'category_id' => Category::findOrCreateByName($reconciliation, BudgetReservedNames::READY_TO_ASSIGN->value),
            'description' => 'Loger adjustment',
            'direction' => $reconciliation->difference > 0 ? Transaction::DIRECTION_CREDIT : Transaction::DIRECTION_DEBIT,
            'status' => Transaction::STATUS_VERIFIED,
            'total' => abs($reconciliation->difference),
            'items' => [],
        ]);

        $reconciliation->addEntry($transaction->lines()->select(['id', 'transaction_id'])->where('account_id', $reconciliation->account_id)->first());
        $reconciliation->checkStatus();
    }

    public function checkLine(Reconciliation $reconciliation, ReconciliationEntry $line, bool $matched = false)
    {
        $reconciliation->entries()->where('id', $line->id)->update([
            'matched' => $matched,
        ]);

        return $reconciliation;
    }

    public function syncTransactions(Reconciliation $reconciliation)
    {
        ReconciliationEntry::destroy($reconciliation->entries()->select('id')->get()->pluck('id'));
        $extraTransactions = $reconciliation->account->transactionsToReconcile(null, $reconciliation->date);
        $reconciliation->addEntries($extraTransactions->toArray());
        $reconciliation->checkStatus();

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

    public function getByDate($teamId, $accountId, $date) {
        return Reconciliation::where([
            'team_id' => $teamId,
            'account_id' => $accountId,
            'date' => $date
        ])
        ->first();
    }
}

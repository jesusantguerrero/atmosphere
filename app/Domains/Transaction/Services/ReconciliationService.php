<?php

namespace App\Domains\Transaction\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Data\ReconciliationParamsData;
use App\Domains\Transaction\Models\Transaction;
use Exception;
use Insane\Journal\Models\Accounting\Reconciliation;
use Insane\Journal\Models\Core\Account;

class ReconciliationService
{
    public function create(Account $account, ReconciliationParamsData $params) {
        $transactions = $account->transactionsToReconcile(null, $params->date);
        if (!count($transactions)) {
            throw new Exception("no transactions matched");
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

    public function saveAdjustment(Reconciliation $reconciliation) {
        $transaction = Transaction::createTransaction([
            'team_id' => $reconciliation->team_id,
            'user_id' => $reconciliation->user_id,
            'account_id' => $reconciliation->account_id,
            'payee_id' => 'new',
            'payee_label' => 'Loger adjustment',
            'date' => now()->format('Y-m-d'),
            'currency_code' => $reconciliation->account->currency_code,
            'category_id' => Category::findOrCreateByName($reconciliation, Category::READY_TO_ASSIGN),
            'description' => 'Loger adjustment',
            'direction' => $reconciliation->difference > 0 ? Transaction::DIRECTION_CREDIT : Transaction::DIRECTION_DEBIT,
            'status' => Transaction::STATUS_VERIFIED,
            'total' => abs($reconciliation->difference),
            'items' => [],
        ]);

        $reconciliation->addEntry($transaction->lines()->select(['id', 'transaction_id'])->where('account_id', $reconciliation->account_id)->first());
        $reconciliation->update(['status' => Reconciliation::STATUS_COMPLETED]);
        $reconciliation->checkStatus();
    }
}

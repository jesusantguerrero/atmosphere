<?php

namespace App\Domains\Transaction\Services;

use App\Domains\Transaction\Data\ReconciliationParamsData;
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
}

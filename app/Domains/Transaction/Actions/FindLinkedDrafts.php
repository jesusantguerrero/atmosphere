<?php

namespace App\Domains\Transaction\Actions;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\LinkedTransaction;
use App\Domains\Transaction\Services\TransactionService;

class FindLinkedDrafts
{
    public const DATE_AFTER = 1;

    public const DATE_BEFORE = 1;

    public function __construct(public int $teamId) {}

    public function handle()
    {
        $drafts = (new TransactionService())->getListByStatus(Transaction::STATUS_DRAFT)->get();
        foreach ($drafts as $draftTransaction) {
            $transactions = Transaction::matchedFor($this->teamId, [
                'total' => $draftTransaction->total,
                'date' => $draftTransaction->date,
            ]);


            if (count($transactions)) {
                foreach ($transactions as $transaction) {
                    try {
                        LinkedTransaction::create([
                            'team_id' => $this->teamId,
                            'user_id' => $draftTransaction->user_id,
                            'transaction_id' => $draftTransaction->id,
                            'linked_transaction_id' => $transaction->id,
                        ]);
                    } catch (Exception $e) {
                        Log::expects($e->getMessage());
                    }
                }
            }
        }
    }
}

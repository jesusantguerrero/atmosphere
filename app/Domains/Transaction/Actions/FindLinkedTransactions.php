<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\Models\LinkedTransaction;
use App\Domains\Transaction\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\Log;

class FindLinkedTransactions {
    public const DATE_AFTER = 1;
    public const DATE_BEFORE = 1;

    public function __construct(
        public int $teamId,
        public int $userId,
        public mixed $transactionData
    ) {}

    public function handle() {
        $transactions = Transaction::select('id')->where([
            'team_id' => $this->teamId,
            'total' => $this->transactionData['total'],
            'status' => Transaction::STATUS_VERIFIED
        ])
        ->whereRaw("date >= SUBDATE(?, interval ? DAY) and date <= ADDDATE(?, INTERVAL ? DAY)",
        [
            $this->transactionData['date'],
            self::DATE_BEFORE,
            $this->transactionData['date'],
            self::DATE_AFTER
        ])->get();

        if (count($transactions)) {
            foreach ($transactions as $transaction) {
                try {
                    LinkedTransaction::create([
                        'team_id' => $this->teamId,
                        'user_id' => $this->userId,
                        'transaction_id' => $this->transactionData['id'],
                        'linked_transaction_id' => $transaction->id
                    ]);
                } catch (Exception $e) {
                    Log::expects($e->getMessage());
                }
            }
        }
    }
}

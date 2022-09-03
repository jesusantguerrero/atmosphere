<?php

namespace App\Listeners;

use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Events\TransactionCreated;

class CreateBudgetTransactionMovement
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TransactionCreated $event)
    {
        $transaction = $event->transaction;
        $type = $transaction->direction == Transaction::DIRECTION_CREDIT ? -1 : 1;
        $amount = $transaction->total * $type;
        $month = substr($transaction->date, 0, 7) . "-01";
        if ($transaction->transaction_category_id) {
            BudgetMonth::updateOrCreate([
                'category_id' => $transaction->transaction_category_id,
                'team_id' => $transaction->team_id,
                'name' => $month,
                'month' => $month,
            ], [
                'activity' => DB::raw("activity + $amount")
            ]);
        }

    }
}

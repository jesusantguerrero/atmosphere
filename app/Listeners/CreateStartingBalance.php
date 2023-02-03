<?php

namespace App\Listeners;

use App\Domains\AppCore\Models\Category;
use Insane\Journal\Events\AccountCreated;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Insane\Journal\Models\Core\Payee;

class CreateStartingBalance
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(AccountCreated $event)
    {
        $account = $event->account;
        $startingBalance = (double) $account->opening_balance;
        if ($account->opening_balance) {
            $session = [
                "team_id" => $account->team_id,
                "user_id" => $account->user_id
            ];

            $payee = Payee::findOrCreateByName($session, 'Starting Balance', ['is_system' => true]);
            $amount = $startingBalance * ($account->type ?? 1);
            $categoryGroupId = null;
            $transactionCategoryId = null;
            $isDeposit = $amount > 0;
            if ($isDeposit) {
                $categoryGroupId = Category::findOrCreateByName($session, Category::INFLOW);
                $transactionCategoryId = Category::findOrCreateByName($session, Category::READY_TO_ASSIGN, $categoryGroupId);
            }

            $categoriesUUID = $categoryGroupId ? "$categoryGroupId:$transactionCategoryId" : "not:needed";

            $formData = array_merge($session, [
                'account_id' => $account->id,
                'payee_id' => $payee->id,
                'date' => Carbon::now()->format('Y-m-d H:i:s'),
                'currency_code' => $account->currency_code ?? "DOP",
                'transaction_category_group_id' => $categoryGroupId,
                'category_id' => $transactionCategoryId,
                'counter_account_id' => $payee->account_id,
                'description' => 'Starting Balance',
                'direction' => $isDeposit ? Transaction::DIRECTION_DEBIT : Transaction::DIRECTION_CREDIT,
                'total' => $amount,
                'items' => [],
                'status' => Transaction::STATUS_VERIFIED,
                'metaData' => json_encode([
                    "resource_id" => "SYSTEM:$account->id:$payee->id:$categoriesUUID",
                    "resource_origin" => 'SYSTEM',
                    "resource_type" => 'transaction',
                ])
            ]);

            Transaction::createTransaction($formData);
        }
    }
}

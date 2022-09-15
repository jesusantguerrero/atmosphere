<?php

namespace App\Domains\Transaction\Imports;

use App\Domains\Imports\ImportConcern;
use App\Domains\Transaction\Services\YNABService;
use Carbon\Carbon;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Payee;
use Insane\Journal\Models\Core\Transaction;

class TransactionsImport extends ImportConcern
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return Transaction::createTransaction($row);
    }

    public function map($row): array
    {
        $money = YNABService::getMoney($row);
        $accountId = Account::guessAccount($this->session, [$row['account']], [
            'currency_code' => $money->currencyCode,
        ]);

        $isTransfer = str_contains($row['payee'], 'Transfer :');
        $direction = $money->direction;
        if (!$isTransfer && $row['category_group']) {
            $categoryGroupId = Category::findOrCreateByName($this->session, $row['category_group']);
            $transactionCategoryId = Category::findOrCreateByName($this->session, $row['category'], $categoryGroupId);
            $payee = Payee::findOrCreateByName($this->session, $row['payee'] ?? 'General Provider');
            $payeeId = $payee->id;
            $counterAccountId = $payee->account_id;
        } else {
            $transfers = explode('Transfer :', $row['payee']);
            $transfer = trim($transfers[1] ?? $transfers[0]);
            $counterAccountId = Account::guessAccount($this->session, [$transfer]);
            $categoryGroupId = null;
            $transactionCategoryId = null;
            $payeeId = null;
            [
                $accountId,
                $counterAccountId,
                $direction
            ] = YNABService::fixTransfer($accountId, $counterAccountId, $money->direction);
        }
        $row['team_id'] = $this->session['team_id'];
        $row['user_id'] = $this->session['user_id'];
        $row['account_id'] = $accountId;
        $row['payee_id'] = $payeeId;
        $row['date'] = Carbon::parse($row['date'])->format('Y-m-d');
        $row['currency_code'] = $money->currencyCode;
        $row['transaction_category_group_id'] = $categoryGroupId;
        $row['category_id'] = $transactionCategoryId;
        $row['counter_account_id'] = $counterAccountId;
        $row['description'] = $row['memo'] ?? '';
        $row['direction'] = $direction;
        $row['total'] = $money->amount;
        $row['items'] = [];
        $row['metaData'] = [
            "resource_id" => 'YNAB',
            "resource_origin" => 'YNAB',
            "resource_type" => 'transaction',
        ];
        $row['is_transfer'] = $isTransfer;
        return $row;
    }
}

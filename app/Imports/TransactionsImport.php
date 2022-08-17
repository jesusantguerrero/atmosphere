<?php

namespace App\Imports;

use App\Helpers\ImportHelper;
use Carbon\Carbon;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Payee;
use Insane\Journal\Models\Core\Transaction;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class TransactionsImport extends ImportHelper
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

    public function getYNABMoney(array $row)  {
        $CODES = [
            'RD' => 'DOP',
            'USD' => 'USD',
        ];
        $inflow = explode('$', $row['inflow']);
        $outflow = explode('$', $row['outflow']);
        $amounts = (object) [
            'inflow' => (double) $inflow[1],
            'outflow' => (double) $outflow[1],
            'currency_code' => $CODES[$inflow[0]] ?? 'USD',
        ];

        $direction = $amounts->inflow > $amounts->outflow ? 'inflow' : 'outflow';

       return [
            'direction' => $direction,
            'amount' => $amounts->$direction,
            'currency_code' => $amounts->currency_code,
       ];
    }

    public function map($row): array
    {
        $money = $this->getYNABMoney($row);
        $accountId = Account::guessAccount($this->session, [$row['account']], [
            'currency_code' => $money['currency_code'],
        ]);
        $isTransfer = str_contains($row['payee'], 'Transfer :');
        if (!$isTransfer && $row['category_group']) {
            $categoryGroupId = Category::findOrCreateByName($this->session, $row['category_group']);
            $transactionCategoryId = Category::findOrCreateByName($this->session, $row['category'], $categoryGroupId);
            $payee = Payee::findOrCreateByName($this->session, $row['payee'] ?? 'General Provider');
            $payeeId = $payee->id;
            $categoryId = $payee->account_id;
        } else {
            $transfers = explode('Transfer :', $row['payee']);
            $transfer = trim($transfers[1] ?? $transfers[0]);
            $categoryGroupId = null;
            $transactionCategoryId = null;
            $payeeId = null;
            $categoryId = Account::guessAccount($this->session, [$transfer]);
        }
        $row['team_id'] = $this->session['team_id'];
        $row['user_id'] = $this->session['user_id'];
        $row['account_id'] = $accountId;
        $row['payee_id'] = $payeeId;
        $row['date'] = Carbon::parse($row['date'])->format('Y-m-d H:i:s');
        $row['currency_code'] = $money['currency_code'];
        $row['transaction_category_group_id'] = $categoryGroupId;
        $row['transaction_category_id'] = $transactionCategoryId;
        $row['category_id'] = $categoryId;
        $row['description'] = $row['memo'] ?? $row['account'];
        $row['direction'] = $money['direction'] == 'inflow' ? Transaction::DIRECTION_DEBIT : Transaction::DIRECTION_CREDIT;
        $row['total'] = $money['amount'];
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

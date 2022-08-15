<?php

namespace App\Imports;

use App\Helpers\ImportHelper;
use App\Models\BudgetMonth;
use Carbon\Carbon;
use Insane\Journal\Models\Core\Category;


class BudgetImport extends ImportHelper
{


    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return BudgetMonth::createBudget($row);
    }

    public function getYNABMoney($currencyValue)  {
        $CODES = [
            'RD' => 'DOP',
            'USD' => 'USD',
        ];
        $inflow = explode('$', str_replace('-', '', $currencyValue));
        $money = floatval(preg_replace('/[^-\d\.]/', '', $currencyValue));
        $amounts = (object) [
            'money' => (double) $money,
            'currency_code' => $CODES[$inflow[0]],
        ];


       return [
            'amount' => $amounts->money,
            'currency_code' => $amounts->currency_code,
       ];
    }

    public function map($row): array
    {
        $budgeted = $this->getYNABMoney($row['budgeted']);
        $activity = $this->getYNABMoney($row['activity']);
        $available = $this->getYNABMoney($row['available']);

        $categoryGroupId = Category::findOrCreateByName($this->session, $row['category_group']);
        $transactionCategoryId = Category::findOrCreateByName($this->session, $row['category'], $categoryGroupId);

        $row['team_id'] = $this->session['team_id'];
        $row['user_id'] = $this->session['user_id'];
        $row['category_id'] = $transactionCategoryId;
        $row['month'] = Carbon::createFromFormat('M Y', $row['month'])->format('Y-m') . "-01";
        $row['name'] = $row['month'];
        $row['currency_code'] = $budgeted['currency_code'];
        $row['budgeted'] = $budgeted['amount'];
        $row['activity'] = $activity['amount'];
        $row['available'] = $available['amount'];
        $row['metaData'] = [
            "resource_id" => 'YNAB',
            "resource_origin" => 'YNAB',
            "resource_type" => 'budget',
        ];
        return $row;
    }
}

<?php

namespace App\Domains\Transaction\Services;

use App\Domains\Transaction\Models\Transaction;

class YNABService
{
    public static function getMoney(array $row)  {
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

       return (object) [
            'currencyCode' => $amounts->currency_code,
            'direction' => $direction == 'inflow' ? Transaction::DIRECTION_DEBIT : Transaction::DIRECTION_CREDIT,
            'amount' => $amounts->$direction,
       ];
    }
}

<?php

namespace App\Domains\Integration\Actions;

use Insane\Journal\Models\Core\Transaction;

trait TransactionAction
{
    public static function fieldConfig() {
        return [
                'account_id' => [
                    'type' => 'id',
                    'required' => true
                ],
                'date' => [
                    'type' => 'date',
                    'required' => true
                ],
                'currency_code' => [
                    'type' => 'string'
                ],
                'category_id' => [
                    'type' => 'string',
                    'required' => true
                ],
                'description' => [
                    'type' => 'string',
                    'required' => false
                ],
                'direction' => [
                    'type' => 'labels',
                    'options' => [Transaction::DIRECTION_CREDIT, Transaction::DIRECTION_DEBIT],
                ],
                'total' => [
                    'type' => 'money',
                    'required' => true
                ],
                'items' => [
                    'type' => 'array',
                    'required' => false
                ],
                'metaData' => [
                    'type' => 'json',
                    'required' => false
                ]
            ];
    }
}

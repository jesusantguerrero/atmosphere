<?php

namespace App\Domains\Integration\Actions\BSC;

trait BSCAction
{
    public static function getSchema(): mixed
    {
        return [
            'description' => [
                'type' => 'string',
                'required' => true,
            ],
            'date' => [
                'type' => 'date',
                'label' => 'Date',

            ],
            'currencyCode' => [
                'type' => 'string',
                'label' => 'currencyCode',
                'required' => true,
            ],
            'amount' => [
                'type' => 'money',
                'label' => 'Amount',
                'required' => true,
            ],
            'payee' => [
                'type' => 'string',
                'label' => 'Payee',
                'required' => true,
            ],
            'categoryGroup' => [
                'type' => 'string',
                'label' => 'categoryGroup',
                'required' => true,
            ],
            'category' => [
                'type' => 'string',
                'label' => 'Category',
                'required' => true,
            ],
        ];
    }
}

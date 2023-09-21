<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\Models\Transaction;

class SearchTransactions
{
    public const DATE_AFTER = 1;

    public const DATE_BEFORE = 1;

    private $searchConfig = [
        'description' => [
            'type' => 'text',
        ],
        'category_id' => [
            'type' => 'array',
        ],
    ];

    private $modelQuery;

    public function __construct()
    {

        $this->modelQuery = Transaction::query();
    }

    public function handle(mixed $conditions)
    {

        foreach ($conditions as $field => $condition) {
            if ($condition) {
                $parserName = $this->searchConfig[$field]['type'];
                \call_user_func([$this, "get$parserName"], $condition, $field);
            }
        }

        return $this->modelQuery->orderBy('date')->where([
            'status' => Transaction::STATUS_VERIFIED,
        ])->get();
    }

    public function gettext($condition, $field)
    {
        $operators = [
            'contains' => 'LIKE',
            'is' => '=',
        ];

        foreach ($condition as $param) {
            if (! $param['operator'] || ! $param['value']) {
                continue;
            }
            $operator = $operators[$param['operator']];
            $value = $operator == 'LIKE' ? "%{$param['value']}%" : $param['value'];
            $this->modelQuery->where($field, $operator, $value);
        }
    }

    public function getarray($condition, $field)
    {
        $this->modelQuery->whereIn($field, $condition);
    }
}

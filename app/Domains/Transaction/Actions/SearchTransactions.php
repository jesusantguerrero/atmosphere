<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionLine;

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

        $this->modelQuery = TransactionLine::query();
    }

    public function hasValidConditions($conditions) {
        foreach ($conditions as $field => $condition) {
            if (!$condition) {
                continue;
            }
            foreach ($condition as $param) {
                if ($param && $param['operator'] &&  $param['value']) {
                        return true;
                }

            }
        }
    }

    public function handle(mixed $conditions)
    {
        $allConditions = [];
        if (!$this->hasValidConditions($conditions)) {
            return null;
        }

        foreach ($conditions as $field => $condition) {
            if ($condition) {
                $parserName = $this->searchConfig[$field]['type'];
                if ($field == 'description') {
                    $field = "concept";
                }
                $allConditions[] = [$parserName, $condition];
                \call_user_func([$this, "get$parserName"], $condition, $field);
            }
        }

        return $this->modelQuery->orderBy('date')->whereHas('transaction', fn ($q) => $q->where([
            'status' => Transaction::STATUS_VERIFIED,
        ]))
        ->whereRaw("(anchor = 1 or is_split =1)")
        ->get();
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

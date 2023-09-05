<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Services\YNABService;
use Illuminate\Support\Carbon;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Payee;

class MapYnabToLoger {
    public static function parse(mixed $row, $session): array {
        [
            'date' => FormulaHelper::parseFormula($taskData->date, $payload),
            'currency_code' => FormulaHelper::parseFormula($taskData->currency_code, $payload),
            'category_id' => $categoryId,
            'description' => FormulaHelper::parseFormula($taskData->description, $payload),
            'direction' => FormulaHelper::parseFormula($taskData->direction, $payload),
            'total' => FormulaHelper::parseFormula($taskData->total, $payload),
            'items' => [],
            'metaData' => [
                "resource_id" => $payload['id'],
                "resource_origin" => $previousTask->name,
                "resource_type" => $trigger->name,
            ]
            ];

        $money = YNABService::getMoney($row);
            $accountId =
            $isTransfer = str_contains($row['payee'], 'Transfer :');
            $direction = $money->direction;
            if (!$isTransfer && $row['category_group']) {
                $categoryGroupId = Category::findOrCreateByName($session, $row['category_group']);
                $transactionCategoryId = Category::findOrCreateByName($session, $row['category'], $categoryGroupId);
                $payee = Payee::findOrCreateByName($session, $row['payee'] ?? 'General Provider');
                $payeeId = $payee->id;
                $counterAccountId = $payee->account_id;
            }
            $row['team_id'] = $session['team_id'];
            $row['user_id'] = $session['user_id'];
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

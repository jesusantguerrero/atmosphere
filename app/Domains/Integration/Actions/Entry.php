<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Integration\Models\Automation;
use App\Domains\Integration\Models\AutomationTaskAction;
use App\Helpers\FormulaHelper;
use App\Models\User;
use App\Notifications\EntryGenerated;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;

class Entry
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  Automation  $automation
     * @param  Google_Calendar_Events  $calendarEvents
     * @return void
     */
    public static function create(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    )
    {
        $taskData = json_decode($task->values);

        $accountName = FormulaHelper::parseFormula($taskData->account_id, $payload);
        $categoryName = FormulaHelper::parseFormula($taskData->category_id, $payload);
        $accountId = Account::guessAccount($automation, [$accountName]);
        $categoryId = Account::guessAccount($automation, [$categoryName]);

        $transaction = Transaction::createTransaction([
            'team_id' => $automation->team_id,
            'user_id' => $automation->user_id,
            'account_id' => $accountId,
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
        ]);
        User::find($automation->user_id)->notify(new EntryGenerated($transaction));
        return $transaction;
    }
}

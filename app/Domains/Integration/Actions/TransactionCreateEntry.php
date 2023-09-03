<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Helpers\FormulaHelper;
use App\Models\User;
use App\Notifications\EntryGenerated;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;

class TransactionCreateEntry implements AutomationActionContract
{
    use TransactionAction;

    public static function handle(
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
        $accountId = Account::guessAccount($automation, [$accountName ?? "General"]);
        $categoryId = Account::guessAccount($automation, [$categoryName ?? "Unknown"]);

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

    public function getName(): string
    {
        return "createTransaction";
    }

    public function label(): string
    {
        return "Create transaction";
    }

    public function getDescription(): string
    {
        return "Create a new transaction";
    }

}

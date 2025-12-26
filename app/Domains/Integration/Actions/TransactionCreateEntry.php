<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Transaction\Models\TransactionLine;
use App\Domains\Transaction\Services\TransactionService;
use App\Helpers\FormulaHelper;
use App\Models\Account;
use App\Models\User;
use App\Notifications\EntryGenerated;
use Insane\Journal\Models\Core\Payee;
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
    ) {
        $taskData = json_decode($task->values);

        $accountId = FormulaHelper::parseFormula($taskData->account_id, $payload);

        if ($payeeName = $payload['payee']) {
            $payee = Payee::findOrCreateByName($automation, $payeeName ?? 'General Provider');
            $lastTransaction = TransactionLine::where([
                'payee_id' => $payee->id,
                'team_id' => $automation->team_id,
            ])->first();
            $transactionCategoryId = $lastTransaction?->category_id;
            $payeeId = $payee->id;
            $counterAccountId = $payee->account_id;
        }

        $description = FormulaHelper::parseFormula($taskData->description, $payload);

        if ($payload['productCode'] ?? false) {
            $account = Account::where('team_id', $automation->team_id)
                ->where('number', $payload['productCode'])
                ->whereRaw('LENGTH(number) > 0 and name like "%?%"', [$payload['productName']])
                ->first();

            if ($account) {
                $accountId = $account->id;
            }
        }

        $transactionData = [
            'team_id' => $automation->team_id,
            'user_id' => $automation->user_id,
            'account_id' => $accountId,
            'payee_id' => $payeeId,
            'payee_name' => $payee?->name,
            'counter_account_id' => $counterAccountId ?? null,
            'date' => FormulaHelper::parseFormula($taskData->date, $payload),
            'currency_code' => FormulaHelper::parseFormula($taskData->currency_code, $payload),
            'category_id' => $transactionCategoryId ?? null,
            'description' => $description,
            'reference' => $payload['messageId'],
            'direction' => FormulaHelper::parseFormula($taskData->direction, $payload),
            'total' => FormulaHelper::parseFormula($taskData->total, $payload),
            'items' => [],
            'meta_data' => json_encode([
                'resource_id' => $payload['id'],
                'resource_origin' => $previousTask->name,
                'resource_type' => $trigger->name,
                'resource_description' => $description,
            ]),
        ];

        $transactionService = new TransactionService;

        if ($transaction = $transactionService->findIfDuplicated($transactionData)) {
            return $transaction;
        }

        $transaction = Transaction::createTransaction($transactionData);
        User::find($automation->user_id)->notify(new EntryGenerated($transaction));

        return $transaction;
    }

    public function getName(): string
    {
        return 'createTransaction';
    }

    public function label(): string
    {
        return 'Create transaction';
    }

    public function getDescription(): string
    {
        return 'Create a new transaction';
    }
}

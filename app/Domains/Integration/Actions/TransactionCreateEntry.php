<?php

namespace App\Domains\Integration\Actions;

use App\Models\User;
use App\Helpers\FormulaHelper;
use App\Notifications\EntryGenerated;
use Insane\Journal\Models\Core\Payee;
use Insane\Journal\Models\Core\Transaction;
use App\Domains\Automation\Models\Automation;
use App\Domains\Transaction\Models\TransactionLine;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Automation\Concerns\AutomationActionContract;

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
            'reference' => $description,
            'direction' => FormulaHelper::parseFormula($taskData->direction, $payload),
            'total' => FormulaHelper::parseFormula($taskData->total, $payload),
            'items' => [],
            'meta_data' => json_encode([
                'resource_id' => $payload['id'],
                'resource_origin' => $previousTask->name,
                'resource_type' => $trigger->name,
                'resource_description' => $description
            ]),
        ];

        $transaction = Transaction::where([
            "team_id" => $transactionData['team_id'],
            'date' => $transactionData['date'],
            'total' => $transactionData['total'],
            'currency_code' => $transactionData['currency_code'],
            'direction' => $transactionData['direction'],
            'payee_id' => $transactionData['payee_id'],
        ])
        ->where(
            fn($q) => $q->where('description', $transactionData['description'])
                        ->orWhere('reference', $transactionData['description'])
        )->first();

        if ($transaction && $transaction->status == 'verified') {
           return $transaction;
        }

        print_r($transactionData);

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

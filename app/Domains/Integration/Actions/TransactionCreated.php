<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use Illuminate\Support\Str;

class TransactionCreated implements AutomationActionContract
{
    use TransactionAction;

    public static function handle(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    ) {
        $config = json_decode($trigger->values);
        if (Str::contains($payload['description'], $config->value)) {
            return $payload;
        }
    }

    public function getName(): string
    {
        return "transactionCreated";
    }

    public function label(): string
    {
        return "On Transaction Created";
    }

    public function getDescription(): string
    {
        return "Triggers when a transaction is created";
    }
}

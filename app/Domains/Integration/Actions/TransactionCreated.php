<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use Illuminate\Support\Str;

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
        $config = json_decode($trigger->values);
        if (Str::contains($payload['description'], $config->value)) {
            return $payload;
        }
    }
}

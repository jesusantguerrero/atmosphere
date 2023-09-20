<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Transaction\Models\Transaction as ModelsTransaction;
use Illuminate\Support\Facades\Log;

class TransactionCreateEntry implements AutomationActionContract
{
    use TransactionAction;

    public static function handle(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        mixed $previousTask = null,
        AutomationTaskAction $trigger = null
    ) {
        $config = json_decode($trigger->values);
        $track = json_decode($automation->track, true);
        $trackId = $track['lastId'] ?? 0;
        $transactions = ModelsTransaction::where('description', 'like', "%$config->value%")->orderBy('date')->get();
        foreach ($transactions as $transaction) {
            $tasks = $automation->tasks;
            $previousTask = $tasks->first();
            $payload = $transaction->toArray();
            foreach ($tasks as $taskIndex => $task) {
                if ($taskIndex !== 0) {
                    $action = $task->name;
                    $actionEntity = $task->entity;
                    try {
                        $payload = $actionEntity::$action($automation, $payload, $task, $previousTask, $trigger);
                        $previousTask = $task;
                    } catch (\Exception $e) {
                        print_r($e->getMessage());
                        Log::error($e->getMessage(), $transaction->toArray());

                        continue;
                    }
                }
            }
            $track['lastId'] = $transaction->id;
        }
        $automation->track = json_encode($track);
        $automation->save();
    }
}

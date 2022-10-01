<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Integration\Models\Automation;
use App\Domains\Integration\Models\AutomationTask;
use App\Domains\Integration\Models\AutomationTaskAction;

class OccurrenceCheckAutomation
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  Automation  $automation
     * @param  Google_Calendar_Events  $calendarEvents
     * @return void
     */
    public static function createOccurrenceCheck(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task = null,
        AutomationTaskAction $previousTask = null,
        AutomationTask $trigger = null
    )
    {
        $taskData = json_decode($task->values);
        return [];
    }


    public static function fieldConfig() {
        return [];
    }
}

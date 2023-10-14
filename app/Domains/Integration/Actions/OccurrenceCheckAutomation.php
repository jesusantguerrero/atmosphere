<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Models\Automation;
use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Domains\Automation\Models\AutomationTaskAction;

class OccurrenceCheckAutomation
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  Google_Calendar_Events  $calendarEvents
     * @return void
     */
    public static function createOccurrenceCheck(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task = null,
        AutomationTaskAction $previousTask = null,
        AutomationTaskAction $trigger = null
    ) {
        $taskData = json_decode($task->values);
        $occurrence = (new RegisterOccurrence())->add(
            $payload['team_id'],
            $taskData->name,
            $payload['date']
        );

        return $occurrence;
    }

    public static function fieldConfig()
    {
        return [];
    }
}

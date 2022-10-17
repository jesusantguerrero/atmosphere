<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Integration\Models\Automation;
use App\Domains\Integration\Models\AutomationTaskAction;

class BHD
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  Automation  $automation
     * @param  Google_Calendar_Events  $calendarEvents
     * @return void
     */
    public static function parseAlert(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    )
    {

        return  (new BHDAlert())->handle($automation, $payload);
    }

     /**
     * Validate and create a new team for the given user.
     *
     * @param  Automation  $automation
     * @param  Google_Calendar_Events  $calendarEvents
     * @return void
     */
    public static function parseNotification(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    )
    {

        return (new BHDNotification())->handle($automation, $payload);
    }

}

<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;

class BHD implements AutomationActionContract
{
    public static function handle(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    )
    {
        $type = self::getMessageType($payload);

        return match ($type) {
             'alert'=> self::parseAlert($automation, $payload, $task, $previousTask, $trigger),
             'notification'=> self::parseNotification($automation, $payload, $task, $previousTask, $trigger),
        };
    }

    public function getName(): string
    {
        return "BHDMessage";
    }

    public function label(): string
    {
        return "BHD Message";
    }

    public function getDescription(): string
    {
        return "Parse an email alert or notification";
    }

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


    public static function getMessageType() {

    }

}

<?php

namespace App\Domains\Automation\Concerns;

use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;

interface AutomationActionContract
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  Automation  $automation
     * @param  Google_Calendar_Events  $calendarEvents
     * @return void
     */
    public static function handle(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    );

    public function getName(): string;
    public function getDescription(): string;
    public function label(): string;
}

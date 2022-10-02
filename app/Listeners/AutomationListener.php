<?php

namespace App\Listeners;

use App\Domains\Integration\Models\Automation;
use App\Domains\Integration\Services\LogerAutomationService;
use App\Events\AutomationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class AutomationListener implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(AutomationEvent $event)
    {
        $automationList = Automation::where([
            'team_id' => $event->teamId,
            'automation_tasks.name' => $event->eventName
        ])
        ->select('automations.*', 'automation_tasks.name as trigger_name')
        ->join('automation_tasks', 'automations.trigger_id', 'automation_tasks.id')
        ->get();

        foreach ($automationList as $automation) {
            LogerAutomationService::run($automation, $event->eventData);
        }
    }
}

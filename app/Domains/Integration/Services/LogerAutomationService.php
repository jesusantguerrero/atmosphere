<?php

namespace App\Domains\Integration\Services;

use App\Domains\Integration\Models\Automation;

class LogerAutomationService {

    const MEAL_PLAN_LIKED = 'mealPlanLiked';

    public static function run(Automation $automation, $eventData = null) {
        echo "starting $automation->name with $automation->id \n";
        $tasks = $automation->tasks;
        $trigger = $automation->triggerTask;

        foreach ($tasks as $index => $task) {
            if ($index == 0) {
                $lastData = $eventData;
                $previousTask = null;
            }
            $entity = $task->entity;
            $action = $task->name;
            $lastData = $entity::$action($automation, $lastData, $task, $previousTask, $trigger);
            $previousTask = $task;
        }
    }
}

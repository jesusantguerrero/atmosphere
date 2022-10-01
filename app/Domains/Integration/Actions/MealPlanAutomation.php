<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Integration\Models\Automation;
use App\Domains\Integration\Models\AutomationTask;
use App\Domains\Integration\Models\AutomationTaskAction;
use App\Domains\Meal\Models\MealPlan;
use App\Domains\Meal\Services\MealService;
use App\Helpers\FormulaHelper;
use App\Models\User;
use App\Notifications\EntryGenerated;
use Illuminate\Support\Carbon;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;

class MealPlanAutomation
{

     /**
     * Validate and create a new team for the given user.
     *
     * @param  Automation  $automation
     * @param  Google_Calendar_Events  $calendarEvents
     * @return void
     */
    public static function mealPlanLiked(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task = null,
        AutomationTaskAction $previousTask = null,
        AutomationTask $trigger = null
    )
    {

        return $payload;
    }

    /**
     * Validate and create a new team for the given user.
     *
     * @param  Automation  $automation
     * @param  Google_Calendar_Events  $calendarEvents
     * @return void
     */
    public static function createMealPlan(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task = null,
        AutomationTaskAction $previousTask = null,
        AutomationTask $trigger = null
    )
    {
        $taskData = json_decode($task->values);

        // $mealId = FormulaHelper::parseFormula($taskData->account_id, $payload) ?? $payload['mealId'];
        // $date = FormulaHelper::parseFormula($taskData->category_id, $payload) ?? $payload['date'];
        $mealId =  $payload['meal_id'];
        $date = Carbon::parse($payload['date'])->addDays(7)->format('Y-m-d');

        $plan = MealService::scheduleMealOnDate(
            $mealId,
            $date,
            $payload
        );

        return $plan;
    }
}

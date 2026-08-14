<?php

namespace App\Http\Controllers\System;

use Illuminate\Http\Request;
use App\Domains\Integration\Models\Integration;
use App\Domains\Automation\Models\AutomationTask;
use App\Domains\Automation\Models\AutomationRecipe;
use App\Domains\Integration\Services\GoogleService;
use App\Domains\Integration\Services\EmailToTasksAutomation;
use App\Domains\Automation\Models\AutomationService;

class IntegrationController
{
    public function __invoke()
    {
        $user = request()->user();

        return inertia('Settings/Integrations/Index', [
            'services' => AutomationService::all(),
            'recipes' => AutomationRecipe::all(),
            'tasks' => AutomationTask::all(),
            'integrations' => Integration::where([
                'team_id' => $user->current_team_id,
                'user_id' => $user->id,
            ])->with(['automations'])->get(),
            'emailToTasks' => EmailToTasksAutomation::status($user),
        ]);
    }

    public function toggleEmailToTasks(Request $request)
    {
        $user = $request->user();
        $status = EmailToTasksAutomation::status($user);

        if (! $status['connected']) {
            return response()->json($status, 422);
        }

        if ($status['enabled']) {
            EmailToTasksAutomation::disable($user);
        } else {
            EmailToTasksAutomation::enable($user, $request->input('query'), $request->input('board'));
        }

        return response()->json(EmailToTasksAutomation::status($user));
    }

    public function social()
    {
        $user = request()->user();
        return inertia('Settings/Integrations/Social', [
            'services' => AutomationService::all(),
            'recipes' => AutomationRecipe::all(),
            'tasks' => AutomationTask::all(),
            'integrations' => Integration::where([
                'team_id' => $user->current_team_id,
                'user_id' => $user->id,
            ])->with(['automations'])->get(),
        ]);
        return GoogleService::setTokens((object) $request->post('credentials'), $request->user()->id);
    }

    public function google(Request $request)
    {
        return GoogleService::setTokens((object) $request->post('credentials'), $request->user()->id);
    }
}

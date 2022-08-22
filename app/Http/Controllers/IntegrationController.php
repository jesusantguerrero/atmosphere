<?php

namespace App\Http\Controllers;

use App\Libraries\GoogleService;
use App\Models\Integrations\AutomationRecipe;
use App\Models\Integrations\AutomationService;
use App\Models\Integrations\AutomationTask;
use App\Models\Integrations\Integration;
use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;

class IntegrationController {
    public function __invoke(Request $request) {
        $user = $request->user();

        return Jetstream::inertia()->render($request, 'Settings/Integrations', [
            "services" => AutomationService::all(),
            "recipes" => AutomationRecipe::all(),
            'tasks' => AutomationTask::all(),
            "integrations" => Integration::where([
                'team_id' => $user->current_team_id,
                'user_id' => $user->id
            ])->with(['automations'])->get()
        ]);
    }

    public function google(Request $request)
    {
       return GoogleService::setTokens((object) $request->post('credentials'), $request->user()->id);
    }
}

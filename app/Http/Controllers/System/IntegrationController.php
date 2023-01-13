<?php

namespace App\Http\Controllers\System;

use App\Domains\Integration\Models\AutomationRecipe;
use App\Domains\Integration\Models\AutomationService;
use App\Domains\Integration\Models\AutomationTask;
use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Services\GoogleService;
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

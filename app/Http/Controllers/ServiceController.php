<?php

namespace App\Http\Controllers;

use App\Domains\Integration\Models\AutomationRecipe;
use App\Domains\Integration\Models\AutomationService;
use App\Domains\Integration\Models\AutomationTask;
use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Services\GoogleService;
use Exception;
use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;

class ServiceController {
    public function google(Request $request)
    {
        return GoogleService::requestAccessToken((object) $request->post('credentials'), $request->user());
    }

    public function acceptOauth(Request $request)
    {
        if (auth()->hasUser()) {
            try {
                GoogleService::setTokens((object) $request->post('credentials'), $request->user());
                return redirect('/integrations');
            } catch (Exception $e) {
                dd($e->getMessage());
                return redirect('/integrations')->with('flash', [
                    'banner' => $e->getMessage(),
                ]);
            }

        } else {

        }
    }
}

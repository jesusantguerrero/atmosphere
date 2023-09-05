<?php

namespace App\Http\Controllers\System;

use App\Domains\Integration\Services\GoogleService;
use Exception;
use Illuminate\Http\Request;

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
                return redirect('/integrations')->with('flash', [
                    'banner' => $e->getMessage(),
                ]);
            }

        } else {

        }
    }
}

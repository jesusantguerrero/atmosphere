<?php

namespace App\Http\Responses;

use App\Models\Setting;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();
        $home = RouteServiceProvider::HOME;

        if ($user) {
            $landingPage = Setting::where([
                'user_id' => $user->id,
                'team_id' => $user->current_team_id,
                'name' => 'landing_page',
            ])->value('value');

            if ($landingPage === 'today') {
                $home = '/today';
            }
        }

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended($home);
    }
}

<?php

namespace App\Http\Responses;

use App\Providers\RouteServiceProvider;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // Today merged into Dashboard — always send freshly-logged-in users to the
        // dashboard. The legacy `landing_page='today'` Setting is a no-op; /today
        // itself redirects to /dashboard for any direct navigation that survived.
        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended(RouteServiceProvider::HOME);
    }
}

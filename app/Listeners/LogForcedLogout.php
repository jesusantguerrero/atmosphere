<?php

namespace App\Listeners;

use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Support\Facades\Log;

class LogForcedLogout
{
    /**
     * A normal sign-out fires Logout; CurrentDeviceLogout only fires when the
     * framework closes the session on the user's behalf — in this app that means
     * Jetstream's AuthenticateSession middleware found the session's password hash
     * out of date. Testers hit this mid-transaction and we had nothing in the logs
     * to tell us why, so record the context that identifies the trigger.
     */
    public function handle(CurrentDeviceLogout $event): void
    {
        $request = request();

        Log::warning('Session force-closed: the session password hash no longer matches the user.', [
            'user_id' => $event->user?->getAuthIdentifier(),
            'guard' => $event->guard,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}

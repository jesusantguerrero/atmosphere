<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Http\Controllers\Inertia\UserProfileController;

/**
 * The user profile settings were split from one page into three routes
 * (Account = Jetstream's own /user/profile, plus Security and Preferences).
 * This extends Jetstream's controller so the Security page reuses its
 * `sessions()` collection and two-factor state validation instead of
 * duplicating that logic.
 */
class ProfileSettingsController extends UserProfileController
{
    /**
     * Password, two-factor authentication, active sessions and the account
     * deletion danger zone.
     */
    public function security(Request $request)
    {
        $this->validateTwoFactorAuthenticationState($request);

        return Jetstream::inertia()->render($request, 'Profile/Security', [
            'confirmsTwoFactorAuthentication' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
            'sessions' => $this->sessions($request)->all(),
        ]);
    }

    /**
     * Personal navigation-module visibility.
     */
    public function preferences(Request $request)
    {
        return Jetstream::inertia()->render($request, 'Profile/Preferences', []);
    }
}

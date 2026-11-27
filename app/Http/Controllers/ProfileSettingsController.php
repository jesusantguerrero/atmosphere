<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Http\Controllers\Inertia\UserProfileController;
use Laravel\Jetstream\Jetstream;

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
            'connectedApps' => ConnectedAppController::forUser($request->user()),
        ]);
    }

    /**
     * What the Account page adds to Jetstream's own: the households the user is in, with
     * their role, and the invitations waiting for their email.
     *
     * @return array{households: list<array{id: int, name: string, owner: string|null, role: string|null, current: bool}>, invitations: list<array{id: int, team: string|null, owner: string|null, role: string|null}>}
     */
    public static function account(User $user): array
    {
        return [
            'households' => $user->allTeams()->load('owner:id,name')->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'owner' => $team->owner?->name,
                'role' => $user->ownsTeam($team) ? 'owner' : $team->membership?->role,
                'current' => (int) $team->id === (int) $user->current_team_id,
            ])->values()->all(),
            'invitations' => TeamInvitation::query()->where('email', $user->email)->with('team.owner:id,name')->get()
                ->map(fn (TeamInvitation $invitation) => [
                    'id' => $invitation->id,
                    'team' => $invitation->team?->name,
                    'owner' => $invitation->team?->owner?->name,
                    'role' => $invitation->role,
                ])->values()->all(),
        ];
    }

    /**
     * Personal navigation-module visibility.
     */
    public function preferences(Request $request)
    {
        return Jetstream::inertia()->render($request, 'Profile/Preferences', [
            'notificationPrefs' => $request->user()->notificationPrefs(),
        ]);
    }
}

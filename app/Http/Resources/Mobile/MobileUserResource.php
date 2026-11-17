<?php

namespace App\Http\Resources\Mobile;

use App\Models\Team;
use App\Models\TeamMemberGrant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class MobileUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->email_verified_at !== null,
            'photo_url' => $this->profile_photo_url,
            'profile_photo_url' => $this->profile_photo_url,
            'has_password' => (bool) $this->password,
            'auth_provider' => $this->google_id ? 'google' : ($this->apple_id ? 'apple' : null),
            'language' => $this->language,
            'current_team_id' => $this->current_team_id,
            'current_team' => $this->currentTeam ? ['id' => $this->currentTeam->id, 'name' => $this->currentTeam->name] : null,
            'role' => $this->currentTeam ? $this->roleIn($this->currentTeam) : null,
            'grants' => $this->currentTeam && $this->isGuestOf($this->currentTeam)
                ? $this->grantsIn($this->currentTeam)->map(fn (TeamMemberGrant $grant) => [
                    'profile_id' => $grant->loger_profile_id,
                    'access' => $grant->access,
                    'name' => $grant->profile?->name,
                    'color' => $grant->profile?->color,
                    'shopping_list_id' => $grant->profile?->shoppingListId(),
                ])->values()
                : [],
            'teams' => $this->allTeams()->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'role' => $this->roleIn($team),
            ])->values(),
            'two_factor_enabled' => $this->hasEnabledTwoFactorAuthentication(),
            'two_factor_confirmed' => filled($this->two_factor_secret) && $this->two_factor_confirmed_at !== null,
        ];
    }
}

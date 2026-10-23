<?php

namespace App\Http\Resources\Mobile;

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
            'two_factor_enabled' => $this->hasEnabledTwoFactorAuthentication(),
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Impersonate;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'language', 'role',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Highest-tier admin. Reserved for destructive operations (delete team,
     * kill-switch feature flags). Historic env-based check stays authoritative
     * so config('atmosphere.superadmin.email') always wins even if the DB
     * `role` column ends up out of sync — env is treated as tier 0 override.
     */
    public function isSuperAdmin(): bool
    {
        if (config('atmosphere.superadmin.email') === $this?->email) {
            return true;
        }

        return $this->role === 'super_admin';
    }

    /**
     * Day-to-day backoffice access — user list, team list, impersonate,
     * non-destructive feature-flag toggles. Super admins are always admins.
     */
    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->role === 'admin';
    }

    /**
     * Gate for lab404/laravel-impersonate. Only admins can start an
     * impersonation session (regular users can be impersonated but never
     * initiate it themselves).
     */
    public function canImpersonate(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Users can be impersonated as long as they aren't a super admin
     * themselves — preserves the principle that only the true owner can
     * act as the true owner.
     */
    public function canBeImpersonated(): bool
    {
        return ! $this->isSuperAdmin();
    }

    public function sendLoginLink()
    {
        return config('atmosphere.superadmin.email') === $this?->email;
    }
}

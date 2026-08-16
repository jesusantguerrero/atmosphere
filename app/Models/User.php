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
        'name', 'email', 'password', 'language', 'role', 'notification_prefs',
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
        'notification_prefs' => 'array',
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

    /**
     * Default notification delivery preferences applied when a user has never
     * touched the settings. Both optional channels default ON — the in-app
     * (`database`) channel is always delivered regardless.
     *
     * @return array{email: bool, push: bool}
     */
    public static function defaultNotificationPrefs(): array
    {
        return ['email' => true, 'push' => true];
    }

    /**
     * Merge the stored preferences over the defaults so a partially-saved or
     * legacy row never leaves a channel undefined.
     *
     * @return array{email: bool, push: bool}
     */
    public function notificationPrefs(): array
    {
        $stored = $this->notification_prefs;

        return array_merge(self::defaultNotificationPrefs(), is_array($stored) ? $stored : []);
    }

    /**
     * Whether this user wants a given optional channel (`email` | `push`).
     */
    public function wantsNotificationChannel(string $channel): bool
    {
        return (bool) ($this->notificationPrefs()[$channel] ?? false);
    }

    /**
     * Route OneSignal pushes to this user by their external ID — the value the
     * frontend passes to `OneSignal.login(...)`, which is the Loger user id.
     */
    public function routeNotificationForOnesignal(): int|string|null
    {
        return $this->id;
    }
}

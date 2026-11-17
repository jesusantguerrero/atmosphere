<?php

namespace App\Models;

use App\Domains\LogerProfile\Models\LogerProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One profile a guest member may see (read) or also work on (write) in a household, e.g.
 * the kid's profile. Guests are denied everything not linked to a granted profile.
 */
class TeamMemberGrant extends Model
{
    const GUEST_ROLE = 'guest';

    const ACCESS_READ = 'read';

    const ACCESS_WRITE = 'write';

    protected $fillable = [
        'team_id',
        'user_id',
        'loger_profile_id',
        'access',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(LogerProfile::class, 'loger_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allows(string $access): bool
    {
        return $access === self::ACCESS_READ || $this->access === self::ACCESS_WRITE;
    }
}

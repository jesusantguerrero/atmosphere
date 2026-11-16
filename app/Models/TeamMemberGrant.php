<?php

namespace App\Models;

use App\Domains\LogerProfile\Models\LogerProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One thing a guest member may use in a household, e.g. the shopping list (write) or one
 * profile's calendar (read). Guests are denied everything else.
 */
class TeamMemberGrant extends Model
{
    const GUEST_ROLE = 'guest';

    const MODULE_SHOPPING = 'shopping';

    const MODULE_CALENDAR = 'calendar';

    const MODULE_BUDGET = 'budget';

    const MODULES = [self::MODULE_SHOPPING, self::MODULE_CALENDAR, self::MODULE_BUDGET];

    const ACCESS_READ = 'read';

    const ACCESS_WRITE = 'write';

    protected $fillable = [
        'team_id',
        'user_id',
        'module',
        'profile_id',
        'scope_id',
        'access',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(LogerProfile::class, 'profile_id');
    }

    public function allows(string $access): bool
    {
        return $access === self::ACCESS_READ || $this->access === self::ACCESS_WRITE;
    }
}

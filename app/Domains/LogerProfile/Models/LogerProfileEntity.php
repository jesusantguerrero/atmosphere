<?php

namespace App\Domains\LogerProfile\Models;

use App\Domains\AppCore\Models\Planner;
use App\Domains\LogerProfile\Services\GuestAlertService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogerProfileEntity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'profile_id',
        'name',
        'entity_type',
        'entity_id',
    ];

    protected $with = ['entity'];

    protected static function booted(): void
    {
        static::created(function (LogerProfileEntity $link) {
            if ($link->entity_type === Planner::class) {
                app(GuestAlertService::class)->eventLinked($link);
            }
        });
    }

    public function entity()
    {
        return $this->morphTo('entity');
    }
}

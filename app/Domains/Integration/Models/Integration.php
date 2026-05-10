<?php

namespace App\Domains\Integration\Models;

use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationService;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'team_id', 'automation_service_id', 'name', 'token', 'hash', 'last_synced_at'];

    public function automations()
    {
        return $this->hasMany(Automation::class, 'integration_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function service()
    {
        return $this->belongsTo(AutomationService::class, 'automation_service_id', 'id');
    }

    public function markSynced(): void
    {
        $this->forceFill(['last_synced_at' => now()])->save();
    }

    protected function casts(): array
    {
        return [
            'config' => 'object',
            'last_synced_at' => 'datetime',
        ];
    }
}

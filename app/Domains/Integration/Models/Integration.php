<?php

namespace App\Domains\Integration\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Domains\Automation\Models\Automation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'team_id', 'automation_service_id', 'name', 'token', 'hash'];

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

    protected $casts = [
        "config" => "object"
    ];
}

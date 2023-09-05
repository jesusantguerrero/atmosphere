<?php

namespace App\Domains\Integration\Models;

use App\Domains\Automation\Models\Automation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use HasFactory;
    protected $fillable = ["user_id", "team_id", "automation_service_id", "name", "token", "hash"];

    public function automations()
    {
        return $this->hasMany(Automation::class, 'integration_id', 'id');
    }
}

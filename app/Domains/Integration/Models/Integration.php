<?php

namespace App\Domains\Integration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use HasFactory;

    public function automations()
    {
        return $this->hasMany(Automation::class, 'integration_id', 'id');
    }
}

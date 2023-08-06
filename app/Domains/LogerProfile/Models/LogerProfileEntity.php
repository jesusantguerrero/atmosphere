<?php

namespace App\Domains\LogerProfile\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogerProfileEntity extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id",
        "team_id",
        "profile_id",
        "name",
        "profile_entity_type",
        "profile_entity_id",
    ];

    protected $with = ['entity'];

    public function entity() {
       return $this->morphTo('entity');
    }
}

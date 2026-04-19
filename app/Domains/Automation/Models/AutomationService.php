<?php

namespace App\Domains\Automation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'type',
        'entity',
        'handler',
        'description',
        'logo',
        'fields',
    ];
}

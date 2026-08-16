<?php

namespace App\Domains\Automation\Models;

use Database\Factories\AutomationTaskActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationTaskAction extends Model
{
    use HasFactory;

    /** The model lives under App\Domains, so Laravel's namespace guess misses the factory. */
    protected static function newFactory(): AutomationTaskActionFactory
    {
        return AutomationTaskActionFactory::new();
    }

    protected $fillable = ['team_id', 'user_id', 'automation_id', 'automation_task_id', 'name', 'entity', 'task_type', 'order', 'accepts_config', 'values'];
}

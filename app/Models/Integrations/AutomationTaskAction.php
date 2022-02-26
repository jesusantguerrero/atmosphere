<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationTaskAction extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id', 'integration_id', 'automation_task_id', 'name', 'values'];
}

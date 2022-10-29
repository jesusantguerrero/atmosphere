<?php

namespace App\Domains\Integration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AutomationTask extends Model
{
    use HasFactory;

    public static function createTask($serviceId, $task, $service, $type) {
        DB::table('automation_tasks')->insert([
            'name' => $task['name'],
            'label' => $task['label'],
            'task_type' => $type,
            'entity' => $service['entity'],
            'description' => $task['description'],
            'automation_service_id' => $serviceId,
            'config' => $task['config'] ?? '',
            'accepts_config' => $task['accept_config'] ?? false,
        ]);
    }
}

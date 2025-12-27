<?php

namespace App\Domains\Automation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'automatable_id',
        'automatable_type',
        'trigger_id',
        'integration_id',
        'name',
        'description',
        'entity',
        'sentence',
        'track',
        'config',
        'status',
    ];

    protected $casts = [
        'config' => 'array',
        'track' => 'array',
        'status' => 'boolean',
        'is_background' => 'boolean',
    ];

    public function recipe()
    {
        return $this->belongsTo('App\Models\Daily\AutomationRecipe', 'automation_recipe_id', 'id');
    }

    public function tasks()
    {
        return $this->hasMany(AutomationTaskAction::class)->orderBy('order');
    }

    public function triggerTask()
    {
        return $this->hasOne(AutomationTaskAction::class)->where('task_type', 'trigger')->latest();
    }

    public function saveTasks($tasks)
    {
        AutomationTaskAction::query()->where('automation_id', $this->id)->delete();
        foreach ($tasks as $index => $task) {
            if (! $task['name']) {
                continue;
            }
            if (empty($task['entity'])) {
                $taskSource = AutomationTask::find($task['automation_task_id']);
                $task['entity'] = $taskSource->entity;
                $task['task_type'] = $taskSource->task_type;
            }

            $this->tasks()->create([
                'team_id' => $this->team_id,
                'user_id' => $this->user_id,
                'entity' => $task['entity'],
                'task_type' => $task['task_type'],
                'order' => $index,
                'automation_id' => $this->id,
                'automation_task_id' => $task['automation_task_id'] ?? null,
                'name' => $task['name'],
                'values' => json_encode($task['values']),
            ]);
        }
    }
}

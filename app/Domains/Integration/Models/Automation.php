<?php

namespace App\Domains\Integration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id', 'trigger_id', 'integration_id', 'name', 'description', 'entity', 'sentence', 'track'];
    public function recipe() {
        return $this->belongsTo('App\Models\Daily\AutomationRecipe', 'automation_recipe_id', 'id');
    }

    public function tasks() {
        return $this->hasMany(AutomationTaskAction::class, 'automation_id', 'id')->orderBy('order');
    }

    public function triggerTask() {
        return $this->hasOne(AutomationTask::class, 'id', 'trigger_id');
    }

    public function saveTasks($tasks) {
        AutomationTaskAction::query()->where('automation_id', $this->id)->delete();
        foreach ($tasks as $index => $task) {
            if (!$task['name']) continue;
            $taskSource = AutomationTask::find($task['automation_task_id']);
            $this->tasks()->create([
                "team_id" => $this->team_id,
                "user_id" => $this->user_id,
                "entity" => $taskSource->entity,
                "task_type" => $taskSource->task_type,
                'order' => $index,
                "automation_id" => $this->id,
                "automation_task_id" => $task['automation_task_id'],
                "name" => $task['name'],
                "values" => json_encode($task['values']),
            ]);
        }
    }
}

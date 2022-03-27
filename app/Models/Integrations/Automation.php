<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id', 'integration_id', 'name', 'description', 'entity', 'sentence', 'track'];
    public function recipe() {
        return $this->belongsTo('App\Models\Daily\AutomationRecipe', 'automation_recipe_id', 'id');
    }

    public function tasks() {
        return $this->hasMany(AutomationTaskAction::class)->orderBy('order');
    }

    public function trigger() {
        return $this->hasOne(AutomationTaskAction::class)->where('task_type', 'trigger');
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

    public function dispatch() {
        echo "starting $this->name with $this->id \n";
        $trigger = $this->tasks()->first();
        $entity = $trigger->entity;
        $action = $trigger->name;
        $entity::$action($this);
    }
}

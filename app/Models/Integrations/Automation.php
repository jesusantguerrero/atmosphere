<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id', 'integration_id', 'name', 'description', 'sentence', 'track'];
    public function recipe() {
        return $this->belongsTo('App\Models\Daily\AutomationRecipe', 'automation_recipe_id', 'id');
    }

    public function tasks() {
        return $this->hasMany('App\Models\Integrations\AutomationTaskAction');
    }

    public function saveTasks($tasks) {
        AutomationTaskAction::query()->where('automation_id', $this->id)->delete();
        foreach ($tasks as $task) {
            if (!$task['name']) continue;
            $this->tasks()->create([
                "team_id" => $this->team_id,
                "user_id" => $this->user_id,
                "automation_id" => $this->id,
                "automation_task_id" => $task['automation_task_id'],
                "name" => $task['name'],
                "values" => json_encode($task['values']),
            ]);
        }
    }
}

<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id', 'project_id', 'board_id', 'integration_id', 'name', 'description', 'sentence', 'track', 'automation_recipe_id', 'config'];
    public function recipe() {
        return $this->belongsTo('App\Models\Daily\AutomationRecipe', 'automation_recipe_id', 'id');
    }
}

<?php

namespace App\Domains\AppCore\Models;

use App\Domains\Integration\Services\LogerAutomationService;
use App\Events\AutomationEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planner extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id','dateable_id', 'dateable_type' ,'date', 'frequency', 'automatic', 'is_liked'];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::updated(function ($updatedModel) {
            if($updatedModel->isDirty('is_liked') && $updatedModel->is_liked) {
                AutomationEvent::dispatch($updatedModel->team_id, LogerAutomationService::MEAL_PLAN_LIKED, $updatedModel->toArray());
            }
            return $updatedModel;
        });
    }

    public function dateable() {
        return $this->morphTo('dateable');
    }


}

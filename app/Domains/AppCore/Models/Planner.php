<?php

namespace App\Domains\AppCore\Models;

use App\Concerns\SupportsDateFrame;
use App\Domains\Automation\Services\LogerAutomationService;
use App\Events\AutomationEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Jetstream\HasTeams;

class Planner extends Model
{
    use HasFactory;
    use HasTeams;
    use SupportsDateFrame;

    const STATUS_PLANNED = 'planned';
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'team_id', 
        'user_id', 
        'dateable_id', 
        'dateable_type', 
        'date', 
        'frequency', 
        'automatic', 
        'is_liked', 
        'status',
        'completed_at',
        'completed_by',
        'completed_resource_id',
        'completed_resource_type',
        'completion_notes',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::updated(function ($updatedModel) {
            if ($updatedModel->isDirty('is_liked') && $updatedModel->is_liked) {
                AutomationEvent::dispatch($updatedModel->team_id, LogerAutomationService::MEAL_PLAN_LIKED, $updatedModel->toArray());
            }

            return $updatedModel;
        });
    }


    public function dateable()
    {
        return $this->morphTo('dateable');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function completedResource()
    {
        return $this->morphTo('completed_resource');
    }

    public function markAsCompleted(?Model $resource, $notes = null)
    {
        $this->update([
            'completed_at' => now(),
            'completed_by' => auth()->id(),
            'completed_resource_id' => $resource?->id,
            'completed_resource_type' => $resource?->getMorphClass(),
            'completion_notes' => $notes,
            'status' => self::STATUS_COMPLETED
        ]);

        return $this;
    }

    public function markAsCancelled($notes = null)
    {
        $this->update([
            'completed_at' => now(),
            'completed_by' => auth()->id(),
            'completion_notes' => $notes,
            'status' => self::STATUS_CANCELLED
        ]);

        return $this;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }
}

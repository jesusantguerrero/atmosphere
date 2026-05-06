<?php

namespace App\Domains\Housing\Models;

use App\Domains\Housing\Contracts\OccurrenceNotifyTypes;
use App\Domains\Transaction\Models\Transaction;
use App\Events\OccurrenceCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Occurrence extends Model
{
    use HasFactory;

    protected $table = 'occurrence_checks';

    protected $fillable = [
        'team_id',
        'user_id',
        'name',
        'last_date',
        'previous_days_count',
        'total_days',
        'avg_days_passed',
        'occurrence_count',
        'log',
        'type',
        'conditions',
        'notify_on_avg',
        'notify_on_last_count',
        'is_active',
    ];

    const DAYS_BEFORE = 3;

    // HM-1: marks an Occurrence as a recurring utility bill so /today's UPCOMING widget
    // can surface "water due in 3 days" alongside credit card billing cycles.
    const TYPE_UTILITY = 'utility';

    // FM-1: marks an Occurrence as a relationship reminder so /relationships can surface
    // "last saw Hope 12 days ago" and nudge when it's been longer than usual.
    const TYPE_RELATIONSHIP = 'relationship';

    const NOTIFY_FIELDS = [
        'last' => [
            'activatedField' => 'notify_on_last_count',
            'countField' => 'previous_days_count',
        ],
        'avg' => [
            'activatedField' => 'notify_on_avg',
            'countField' => 'avg_days_passed',
        ],
    ];

    protected $casts = [
        'conditions' => 'array',
        'log' => 'array',
        'last_date' => 'date',
    ];

    protected static function booted()
    {
        static::created(function ($newOccurrence) {
            OccurrenceCreated::dispatch($newOccurrence);
        });
    }

    public static function getLinkedModels()
    {
        return [
            Transaction::class => [
                'key' => Transaction::class,
                'label' => 'Transactions',
                'subtypes' => ['label', 'group', 'category', 'payee'],
            ],
        ];
    }

    public static function scopeByTeam($query, int $teamId)
    {
        return $query->where([
            'team_id' => $teamId,
        ]);
    }

    public static function scopeByName($query, string $name)
    {
        $query->where([
            'name' => $name,
        ]);
    }

    public static function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Days remaining until the next expected occurrence based on avg cadence.
     * Negative when overdue. Null when there's no last_date yet (brand new).
     */
    public function daysUntilNext(): ?int
    {
        if (! $this->last_date || ! $this->avg_days_passed) {
            return null;
        }

        return (int) round(now()->startOfDay()->diffInDays(
            $this->last_date->copy()->addDays((int) $this->avg_days_passed),
            false
        ));
    }

    public function currentCount()
    {
        return $this->last_date->diffInDays(now());
    }

    public function diffWithAvg()
    {
        return $this->currentCount() - $this->avg_days_passed;
    }

    public function diffWithLastDuration()
    {
        return $this->currentCount() - $this->previous_days_count;
    }

    public function isCloseToAvg()
    {
        $days = $this->avg_days_passed - $this->currentCount();

        return $days == self::DAYS_BEFORE || $days == 0;
    }

    public function isCloseToLastDuration()
    {
        $days = $this->previous_days_count - $this->currentCount();

        return $days == self::DAYS_BEFORE || $days == 0;
    }

    public static function getForNotificationType(OccurrenceNotifyTypes $type)
    {
        $activatedField = self::NOTIFY_FIELDS[$type->value]['activatedField'];
        $countField = self::NOTIFY_FIELDS[$type->value]['countField'];

        return Occurrence::where($activatedField, true)
            ->where($countField, '>', 1)
            ->get()
            ->filter(fn ($occurrence) => $type->value == OccurrenceNotifyTypes::AVG->value
            ? $occurrence->isCloseToAvg()
            : $occurrence->isCloseToLastDuration()
            );
    }
}

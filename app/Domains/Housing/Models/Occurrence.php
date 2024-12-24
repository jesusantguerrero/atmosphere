<?php

namespace App\Domains\Housing\Models;

use App\Events\OccurrenceCreated;
use Illuminate\Database\Eloquent\Model;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domains\Housing\Contracts\OccurrenceNotifyTypes;

class Occurrence extends Model
{
    use HasFactory;

    protected $table ="occurrence_checks";

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
        'last_date' => 'date'
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

    public static function scopeByTeam($query, int $teamId) {
        return $query->where([
            'team_id' => $teamId,
        ]);
    }

    public static function scopeByName($query, string $name) {
        $query->where([
            'name' => $name,
        ]);
    }

    public function currentCount() {
        return $this->last_date->diffInDays(now());
    }

    public function diffWithAvg() {
        return $this->currentCount() - $this->avg_days_passed;
    }

    public function diffWithLastDuration() {
        return $this->currentCount() - $this->previous_days_count;
    }

    public function isCloseToAvg() {
        $days = $this->avg_days_passed - $this->currentCount();
        return $days == self::DAYS_BEFORE || $days == 0;
    }

    public function isCloseToLastDuration() {
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
            :$occurrence->isCloseToLastDuration()
        );
    }
}

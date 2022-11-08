<?php

namespace App\Domains\Housing\Models;

use App\Domains\Housing\Contracts\OccurrenceNotifyTypes;
use App\Domains\Transaction\Models\Transaction;
use App\Events\OccurrenceCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OccurrenceCheck extends Model
{
    use HasFactory;
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
        'is_active'
    ];
    const DAYS_BEFORE = 3;
    const NOTIFY_FIELDS = [
        'last' => [
            'activatedField' => 'notify_on_last_count',
            'countField' => 'previous_days_count'
        ],
        'avg' =>  [
            'activatedField' => 'notify_on_avg',
            'countField' => 'avg_days_passed'
        ]
    ];

    protected $casts = [
        'conditions' => 'array',
        'log' => 'array'
    ];

    protected static function booted() {
        static::created(function($newOccurrence) {
            OccurrenceCreated::dispatch($newOccurrence);
        });
    }

    static function getLinkedModels() {
        return [
            Transaction::class => [
                'key' => Transaction::class,
                'label' => 'Transactions',
                'subtypes' => ['label', 'group', 'category', 'payee']
            ]
        ];
    }

    static function getForNotificationType(OccurrenceNotifyTypes $type) {
        $daysBefore = self::DAYS_BEFORE;
        $activatedField = self::NOTIFY_FIELDS[$type->value]['activatedField'];
        $countField =self::NOTIFY_FIELDS[$type->value]['countField'];
        return  OccurrenceCheck::where($activatedField, true)
        ->whereRaw("DATEDIFF( date_format(now(), '%Y-%m-%d'), last_date) > ($countField - $daysBefore)")
        ->get();
    }
}

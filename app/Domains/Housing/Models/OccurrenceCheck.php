<?php

namespace App\Domains\Housing\Models;

use App\Domains\Transaction\Models\Transaction;
use App\Events\OccurrenceCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}

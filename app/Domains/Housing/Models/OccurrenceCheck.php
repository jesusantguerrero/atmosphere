<?php

namespace App\Domains\Housing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OccurrenceCheck extends Model
{
    use HasFactory;
    protected $fillable = [
        'team_id',
        'name',
        'last_date',
        'previous_days_count',
        'total_days',
        'avg_days_passed',
        'occurrence_count',
        'log'
    ];
}

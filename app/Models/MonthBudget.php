<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MonthBudget extends Model
{
    use HasFactory;
    protected $fillable = ['category_id', 'month', 'name', 'budgeted'];


    public static function getMonthAssignments($teamId, $date) {
        $yearMonth = (new DateTime($date))->format('Y-m'). "-01";
        return self::where('team_id', $teamId)->where('month', $yearMonth)->get();
    }
}

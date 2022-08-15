<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthBudget extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id', 'category_id', 'month', 'name', 'budgeted', 'activity' , 'available'];

    public static function getMonthAssignments($teamId, $date) {
        $yearMonth = (new DateTime($date))->format('Y-m'). "-01";
        return self::where('team_id', $teamId)->where('month', $yearMonth)->get();
    }

    public static function getMonthAssignmentTotal($teamId, $date, $field="budgeted") {
        $yearMonth = (new DateTime($date))->format('Y-m'). "-01";
        return self::where('team_id', $teamId)->where('month', $yearMonth)->sum($field);
    }

    public static function createBudget($data) {
        $monthBudget = self::where([
            "team_id" => $data['team_id'],
            'month' => $data['month'],
            'currency_code' => $data['currency_code'],
            'category_id' => $data['category_id'],
        ])->get();
        if ($monthBudget->count()) {
            $monthBudget->first()->update($data);
            $monthBudget = $monthBudget->first();
        } else {
            $monthBudget = self::create($data);
        }
        return $monthBudget;
    }
}

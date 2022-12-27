<?php

namespace App\Domains\Budget\Models;

use App\Domains\AppCore\Models\Category;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BudgetMonth extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id', 'category_id', 'month', 'name', 'budgeted', 'activity' , 'available'];

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public static function getMonthAssignments($teamId, $date) {
        $yearMonth = (new DateTime($date))->format('Y-m'). "-01";
        return self::where('team_id', $teamId)->where('month', $yearMonth)->get();
    }

    public static function getMonthAssignmentTotal($teamId, $date, $field="budgeted") {
        $yearMonth = (new DateTime($date))->format('Y-m'). "-01";
        return self::where('team_id', $teamId)->where('month', $yearMonth)->sum($field);
    }

    public static function createBudget($data, $startingBalance = true) {
        $month = self::updateOrCreate([
            "team_id" => $data['team_id'],
            'month' => $data['month'],
            'name' => $data['name'],
            'category_id' => $data['destination_category_id'] ?? $data['category_id'],
        ], [
            "user_id" => $data['user_id'],
            'budgeted' => $data['budgeted'] ?? 0,
            'activity' => $data['activity'] ?? 0,
        ]);
        return $month;
    }

    public static function updateBudget($data, $startingBalance = true) {
        $month = self::updateOrCreate([
            "team_id" => $data['team_id'],
            'month' => $data['month'],
            'name' => $data['name'],
            'category_id' => $data['destination_category_id'] ?? $data['category_id'],
        ], [
            "user_id" => $data['user_id'],
            'budgeted' => DB::raw("budgeted + {$data['budgeted']}"),
            'activity' => DB::raw("activity + {$data['activity']}"),
        ]);
        return $month;
    }

    public static function getSavingBalance($teamId, $endMonth) {
        return DB::query()
        ->whereIn('budgets.target_type', ['saving_balance'])
        ->whereRaw("date_format(month, '%Y-%m') <= '$endMonth'")
        ->where('budgets.team_id', $teamId)
        ->from('budget_months')
        ->join('budgets', 'budgets.category_id', 'budget_months.category_id')
        ->sum(DB::raw("budgeted + activity"));
    }
}

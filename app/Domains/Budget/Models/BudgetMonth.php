<?php

namespace App\Domains\Budget\Models;

use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Models\Core\Account;
use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Data\BudgetReservedNames;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BudgetMonth extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'category_id',
        'month',
        'name',
        'budgeted',
        'activity',
        'available',
        'funded_spending',
        'payments',
        'left_from_last_month',
        'overspending_previous_month',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function account()
    {
        return $this->hasOneThrough(Account::class, Category::class, 'account_id', 'id');
    }

    public static function getMonthAssignments($teamId, $date)
    {
        $yearMonth = (new DateTime($date))->format('Y-m').'-01';

        return self::where('team_id', $teamId)->where('month', $yearMonth)->get();
    }

    public static function getMonthAssignmentTotal($teamId, $date, $field = 'budgeted')
    {
        $yearMonth = (new DateTime($date))->format('Y-m').'-01';
        $balance = self::where('budget_months.team_id', $teamId)
            ->where('month', $yearMonth)
            ->orderByDesc('budgeted')
            ->join('categories', fn ($q) => $q->on('categories.id', 'category_id')
                ->whereNot('categories.name', BudgetReservedNames::READY_TO_ASSIGN->value)
            )
            ->where('budgeted', '>', 0)
            ->sum($field);

        return $balance;
    }

    public static function createBudget($data, $startingBalance = true)
    {
        $month = self::updateOrCreate([
            'team_id' => $data['team_id'],
            'month' => $data['month'],
            'name' => $data['name'],
            'category_id' => $data['destination_category_id'] ?? $data['category_id'],
        ], [
            'user_id' => $data['user_id'],
            'budgeted' => $data['budgeted'] ?? 0,
            'activity' => $data['activity'] ?? 0,
        ]);

        return $month;
    }

    public static function updateBudget($data, $startingBalance = true)
    {
        $month = self::updateOrCreate([
            'team_id' => $data['team_id'],
            'month' => $data['month'],
            'name' => $data['name'],
            'category_id' => $data['destination_category_id'] ?? $data['category_id'],
        ], [
            'user_id' => $data['user_id'],
            'budgeted' => DB::raw("budgeted + {$data['budgeted']}"),
            'activity' => DB::raw("activity + {$data['activity']}"),
        ]);

        return $month;
    }

    public function updateActivity()
    {
        $monthDate = Carbon::createFromFormat('Y-m-d', $this->month);
        if (! $this->category->account) {
            $this->update([
                'activity' => $this->category->getMonthBalance($monthDate->format('Y-m'))->balance,
            ]);
        } else {
            $accountTransactions = $this->category->account->getMonthBalance($monthDate->format('Y-m'))->balance;
            $this->update([
                'activity' => $accountTransactions,
            ]);
        }
    }

    public static function getSavingBalance($teamId, $endMonth)
    {
        return DB::query()
            ->whereIn('budgets.target_type', ['saving_balance'])
            ->whereRaw("date_format(month, '%Y-%m') <= '$endMonth'")
            ->where('budget_targets.team_id', $teamId)
            ->from('budget_months')
            ->join('budget_targets', 'budget_targets.category_id', 'budget_months.category_id')
            ->sum(DB::raw('budgeted + activity'));
    }
}

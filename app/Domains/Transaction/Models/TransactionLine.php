<?php

namespace App\Domains\Transaction\Models;

use App\Models\Team;
use App\Domains\AppCore\Models\Planner;
use Insane\Journal\Models\Core\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domains\Transaction\Traits\TransactionLineTrait;
use Insane\Journal\Models\Core\TransactionLine as CoreTransactionLine;

class TransactionLine extends CoreTransactionLine
{
    use HasFactory;
    use TransactionLineTrait;

    public function schedule()
    {
        return $this->morphOne(Planner::class, 'dateable', 'dateable_type', 'dateable_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function categoryGroup()
    {
        return $this->hasOneThrough(Category::class, Category::class, 'parent_id', 'parent_id');
    }

    public function linked()
    {
        return $this->hasMany(LinkedTransaction::class);
    }

    public function copy($data = [])
    {
        $transactionNew = self::create(array_merge($this->toArray(), $data));
        foreach ($this->lines as $line) {
            $transactionNew->lines()->create($line->toArray());
        }

        return $transactionNew;
    }

    public static function matchedFor($teamId, $searchParams)
    {
        return Transaction::select('id')->where([
            'team_id' => $teamId,
            'total' => $searchParams['total'],
            'status' => Transaction::STATUS_VERIFIED,
        ])
            ->whereRaw('date >= SUBDATE(?, interval ? DAY) and date <= ADDDATE(?, INTERVAL ? DAY)',
                [
                    $searchParams['date'],
                    $searchParams['datesBefore'] ?? 1,
                    $searchParams['date'],
                    $searchParams['datesAfter'] ?? 1,
                ])->get();
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\TransactionFactory::new();
    }
}

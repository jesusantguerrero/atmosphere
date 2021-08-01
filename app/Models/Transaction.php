<?php

namespace App\Models;

use App\Models\MealPlan;
use Illuminate\Database\Eloquent\Model;
use Insane\Journal\TransactionLine;

class Transaction extends Model
{
    const STATUS_PLANNED = 'planned';
    const STATUS_DRAFT = 'draft';
    const STATUS_VERIFIED = 'verified';
    const STATUS_CANCELED = 'canceled';

    protected $fillable = ['team_id','user_id', 'transactionable_id', 'transactionable_type' , 'date','number', 'description', 'direction', 'notes', 'total', 'status'];
    protected $with = ['schedule'];
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {

        static::creating(function ($transaction) {
            self::setNumber($transaction);
        });

        static::deleting(function ($transaction) {
            TransactionLine::where('transaction_id', $transaction->id)->delete();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(team::class);
    }

    public function mainLine() {
        return $this->hasOne('Insane\Journal\TransactionLine', 'transaction_id')->where('anchor', true);
    }

    public function category() {
        return $this->hasOne('Insane\Journal\TransactionLine', 'transaction_id')->where('anchor', false);
    }

    public function schedule() {
        return $this->morphOne(MealPlan::class, 'dateable', 'dateable_type', 'dateable_id', );
    }

    public function lines() {
        return $this->hasMany('Insane\Journal\TransactionLine', 'transaction_id');
    }

    //  Utils
    static public function setNumber($transaction) {
        $isInvalidNumber = true;

        if ($transaction->number) {
            $isInvalidNumber = Transaction::where([
                "team_id" => $transaction->team_id,
                "number" => $transaction->number,
            ])->whereNotIn(
                "id", [$transaction->id]
            )->get();

            $isInvalidNumber = count($isInvalidNumber);
        }

        if ($isInvalidNumber) {
            $result = Transaction::where([
                "team_id" => $transaction->team_id,
            ])->max('number');
            $transaction->number = $result + 1;
        }
    }

    static public function createTransaction($transactionData) {
        $transaction = Transaction::create($transactionData);
        $transaction->createLines($transactionData, $transactionData['items'] ?? []);
    }

    public function createLines($transactionData, $items = []) {
        TransactionLine::query()->where('transaction_id', $this->id)->delete();
        if (!count($items)) {
            $this->lines()->create([
                "amount" => $this->total,
                "concept" => $this->description,
                "index" => 0,
                "anchor" => 1,
                "type"=> $this->direction == 'DEPOSIT' ? 1 : -1,
                "account_id" => $transactionData['account_id'],
                "category_id" => 0,
                "team_id" => $this->team_id,
                "user_id" => $this->user_id
            ]);

            $this->lines()->create([
                "amount" => $this->total,
                "concept" => $this->description,
                "index" => 1,
                "type"=> $this->direction == 'DEPOSIT' ? -1 : 1,
                "account_id" => $transactionData['category_id'],
                "category_id" => 0,
                "team_id" => $this->team_id,
                "user_id" => $this->user_id
            ]);

        } else {
            foreach ($items as $item) {
                $this->lines->create([
                    "amount" => $item['amount'],
                    "concept" => $item['concept'],
                    "index" => $item['index'],
                    "type"=> $item['type'],
                    "account_id" => $item['account_id'],
                    "category_id" => $item['category_id'],
                    "team_id" => $this->team_id,
                    "user_id" => $this->user_id
                ]);
            }
        }
    }

    public function copy($data = []) {
        $transactionNew = Self::create(array_merge($this->toArray(), $data));
        foreach ($this->lines as $line) {
            $transactionNew->lines()->create($line->toArray());
        }
        return $transactionNew;
    }

    public function scopeGetByMonth($query, $startDate, $endDate = null) {
        $query
        ->when($startDate && !$endDate, function ($query) use ($startDate) {
            $query->where("date", '=',  $startDate);
        })
        ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
            $query->where("date", '>=',  $startDate);
            $query->where("date", '<=', $endDate);
        })
        ->orderByDesc('date')->orderByDesc('number')
        ->with(['mainLine', 'lines', 'category', 'mainLine.account', 'category.account']);
    }

    public static function parser($transaction) {
        return [
            'id' => $transaction->id,
            'date' => $transaction->date,
            'number' => $transaction->number,
            'description' => $transaction->description,
            'direction' => $transaction->direction,
            'account' => $transaction->mainLine ? $transaction->mainLine->account: null,
            'category' => $transaction->mainLine ? $transaction->category->account : null,
            'total' => $transaction->total,
            'lines' => $transaction->lines,
            'mainLine' => $transaction->mainLine,
            'schedule' => $transaction->schedule,
        ];
    }
}

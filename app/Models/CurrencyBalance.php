<?php

namespace App\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Insane\Journal\Models\Core\Account;

class CurrencyBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'account_id',
        'currency_code',
        'balance',
        'pending_balance',
        'last_updated'
    ];

    protected $casts = [
        'balance' => 'decimal:4',
        'pending_balance' => 'decimal:4',
        'last_updated' => 'datetime'
    ];

    /**
     * Relationship to Account
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Relationship to Team
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relationship to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the total balance (balance + pending_balance)
     */
    public function getTotalBalanceAttribute()
    {
        return $this->balance + $this->pending_balance;
    }

    /**
     * Update the balance for a specific currency
     */
    public function updateBalance(float $amount, bool $isPending = false)
    {
        if ($isPending) {
            $this->pending_balance += $amount;
        } else {
            $this->balance += $amount;
        }
        
        $this->last_updated = now();
        $this->save();
    }

    /**
     * Set the balance for a specific currency
     */
    public function setBalance(float $amount, bool $isPending = false)
    {
        if ($isPending) {
            $this->pending_balance = $amount;
        } else {
            $this->balance = $amount;
        }
        
        $this->last_updated = now();
        $this->save();
    }

    /**
     * Transfer pending balance to regular balance
     */
    public function transferPendingToBalance(float $amount = null)
    {
        $transferAmount = $amount ?? $this->pending_balance;
        
        if ($transferAmount > $this->pending_balance) {
            throw new \InvalidArgumentException('Transfer amount cannot exceed pending balance');
        }
        
        $this->pending_balance -= $transferAmount;
        $this->balance += $transferAmount;
        $this->last_updated = now();
        $this->save();
        
        return $transferAmount;
    }

    /**
     * Find or create a currency balance record
     */
    public static function findOrCreate(int $accountId, string $currencyCode, int $teamId, int $userId)
    {
        return static::firstOrCreate(
            [
                'account_id' => $accountId,
                'currency_code' => $currencyCode
            ],
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'balance' => 0,
                'pending_balance' => 0,
                'last_updated' => now()
            ]
        );
    }
}
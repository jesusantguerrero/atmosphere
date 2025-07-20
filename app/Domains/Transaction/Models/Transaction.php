<?php

namespace App\Domains\Transaction\Models;

use App\Models\Team;
use App\Models\CurrencyBalance;
use App\Domains\AppCore\Models\Planner;
use Insane\Journal\Models\Core\Category;
use App\Domains\Transaction\Traits\TransactionTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Insane\Journal\Models\Core\Transaction as CoreTransaction;

class Transaction extends CoreTransaction
{
    use HasFactory;
    use TransactionTrait;

    /**
     * Additional fillable fields for multi-currency support
     */
    protected $fillable = [
        'exchange_rate',
        'exchange_amount'
    ];

    const STATUS_PLANNED = 'planned';

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
        return Transaction::select('id')
        ->where([
            'team_id' => $teamId,
            'total' => $searchParams['total'],
        ])
        ->whereIn("status", [Transaction::STATUS_DRAFT, Transaction::STATUS_VERIFIED])
        ->whereRaw('date >= SUBDATE(?, interval ? DAY) and date <= ADDDATE(?, INTERVAL ? DAY)',
        [
            $searchParams['date'],
            $searchParams['datesBefore'] ?? 1,
            $searchParams['date'],
            $searchParams['datesAfter'] ?? 1,
        ])->get();
    }

    /**
     * Calculate exchange rate from total and exchange amount
     * 
     * @param float $total The amount in the secondary currency
     * @param float $exchangeAmount The amount in the primary currency
     * @return float The calculated exchange rate
     */
    public function calculateExchangeRate(float $total, float $exchangeAmount): float
    {
        if ($total == 0) {
            throw new \InvalidArgumentException('Total amount cannot be zero for exchange rate calculation');
        }
        
        return round($exchangeAmount / $total, 6);
    }

    /**
     * Relationship to currency balances through the account
     */
    public function currencyBalances()
    {
        return $this->hasManyThrough(
            CurrencyBalance::class,
            \Insane\Journal\Models\Core\Account::class,
            'id', // Foreign key on accounts table
            'account_id', // Foreign key on currency_balances table
            'account_id', // Local key on transactions table
            'id' // Local key on accounts table
        );
    }

    /**
     * Get pending balance for a specific currency from the transaction's account
     * 
     * @param string $currencyCode The currency code to get pending balance for
     * @return float The pending balance amount
     */
    public function getPendingBalanceInCurrency(string $currencyCode): float
    {
        if (!$this->account) {
            return 0.0;
        }

        $currencyBalance = $this->account->currencyBalances()
            ->where('currency_code', $currencyCode)
            ->first();

        return $currencyBalance ? (float) $currencyBalance->pending_balance : 0.0;
    }

    /**
     * Get all pending balances by currency for the transaction's account
     * 
     * @return array Array of currency codes and their pending balances
     */
    public function getAllPendingBalances(): array
    {
        if (!$this->account) {
            return [];
        }

        $balances = [];
        
        // Get all currency balances for this account
        $currencyBalances = $this->account->currencyBalances()
            ->where('pending_balance', '!=', 0)
            ->get();

        foreach ($currencyBalances as $currencyBalance) {
            $balances[$currencyBalance->currency_code] = (float) $currencyBalance->pending_balance;
        }

        return $balances;
    }

    /**
     * Update pending balance for a specific currency
     * 
     * @param string $currencyCode The currency code
     * @param float $amount The amount to add to pending balance
     * @return void
     */
    public function updatePendingBalanceInCurrency(string $currencyCode, float $amount): void
    {
        if (!$this->account) {
            throw new \RuntimeException('Transaction must have an associated account to update currency balances');
        }

        $currencyBalance = $this->account->getOrCreateCurrencyBalance($currencyCode);
        $currencyBalance->updateBalance($amount, true); // true for pending balance
    }

    /**
     * Transfer pending balance to regular balance for a specific currency
     * 
     * @param string $currencyCode The currency code
     * @param float|null $amount The amount to transfer (null for all pending)
     * @return float The amount transferred
     */
    public function transferPendingToBalance(string $currencyCode, ?float $amount = null): float
    {
        if (!$this->account) {
            throw new \RuntimeException('Transaction must have an associated account to transfer currency balances');
        }

        $currencyBalance = $this->account->currencyBalances()
            ->where('currency_code', $currencyCode)
            ->first();

        if (!$currencyBalance) {
            return 0.0;
        }

        return $currencyBalance->transferPendingToBalance($amount);
    }

    /**
     * Check if this transaction has currency conversion data
     * 
     * @return bool True if exchange_rate and exchange_amount are set
     */
    public function hasCurrencyConversion(): bool
    {
        return !is_null($this->exchange_rate) && !is_null($this->exchange_amount);
    }

    /**
     * Get the converted amount in the primary currency
     * 
     * @return float|null The converted amount or null if no conversion data
     */
    public function getConvertedAmount(): ?float
    {
        return $this->exchange_amount;
    }

    /**
     * Get the exchange rate used for this transaction
     * 
     * @return float|null The exchange rate or null if no conversion data
     */
    public function getExchangeRate(): ?float
    {
        return $this->exchange_rate;
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

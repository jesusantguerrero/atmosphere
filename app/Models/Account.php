<?php

namespace App\Models;

use App\Models\CurrencyBalance;
use Insane\Journal\Models\Core\Account as BaseAccount;

class Account extends BaseAccount
{
    /**
     * Initialize the model and merge fillable fields
     */
    public function __construct(array $attributes = [])
    {
        // Merge parent fillable with our additional fields
        $this->fillable = array_merge(
            $this->fillable ?? [],
            [
                'is_multi_currency',
                'secondary_currencies'
            ]
        );
        
        // Merge parent casts with our additional casts
        $this->casts = array_merge(
            $this->casts ?? [],
            [
                'is_multi_currency' => 'boolean',
                'secondary_currencies' => 'array'
            ]
        );
        
        parent::__construct($attributes);
    }

    /**
     * Relationship to currency balances
     */
    public function currencyBalances()
    {
        return $this->hasMany(CurrencyBalance::class);
    }

    /**
     * Get the primary currency of the account
     */
    public function getPrimaryCurrency(): string
    {
        return $this->currency_code ?? 'USD';
    }

    /**
     * Get the secondary currencies supported by this account
     */
    public function getSecondaryCurrencies(): array
    {
        return $this->secondary_currencies ?? [];
    }

    /**
     * Check if this account supports multiple currencies
     */
    public function isMultiCurrency(): bool
    {
        return $this->is_multi_currency ?? false;
    }

    /**
     * Get all supported currencies (primary + secondary)
     */
    public function getAllSupportedCurrencies(): array
    {
        return array_merge([$this->getPrimaryCurrency()], $this->getSecondaryCurrencies());
    }

    /**
     * Get balance for a specific currency
     */
    public function getBalanceInCurrency(string $currencyCode): float
    {
        // If it's the primary currency, use the existing balance calculation
        if ($currencyCode === $this->getPrimaryCurrency()) {
            return (float) $this->balance;
        }

        // For secondary currencies, get from currency_balances table
        $currencyBalance = $this->currencyBalances()
            ->where('currency_code', $currencyCode)
            ->first();

        return $currencyBalance ? (float) $currencyBalance->balance : 0.0;
    }

    /**
     * Get pending balance for a specific currency (for credit cards)
     */
    public function getPendingBalanceInCurrency(string $currencyCode): float
    {
        $currencyBalance = $this->currencyBalances()
            ->where('currency_code', $currencyCode)
            ->first();

        return $currencyBalance ? (float) $currencyBalance->pending_balance : 0.0;
    }

    /**
     * Get total balance (balance + pending) for a specific currency
     */
    public function getTotalBalanceInCurrency(string $currencyCode): float
    {
        if ($currencyCode === $this->getPrimaryCurrency()) {
            return $this->getBalanceInCurrency($currencyCode) + $this->getPendingBalanceInCurrency($currencyCode);
        }

        $currencyBalance = $this->currencyBalances()
            ->where('currency_code', $currencyCode)
            ->first();

        return $currencyBalance ? (float) $currencyBalance->total_balance : 0.0;
    }

    /**
     * Get all currency balances for this account
     */
    public function getAllCurrencyBalances(): array
    {
        $balances = [];
        
        // Add primary currency balance
        $primaryCurrency = $this->getPrimaryCurrency();
        $balances[$primaryCurrency] = [
            'currency_code' => $primaryCurrency,
            'balance' => $this->getBalanceInCurrency($primaryCurrency),
            'pending_balance' => $this->getPendingBalanceInCurrency($primaryCurrency),
            'total_balance' => $this->getTotalBalanceInCurrency($primaryCurrency),
            'is_primary' => true
        ];

        // Add secondary currency balances
        foreach ($this->currencyBalances as $currencyBalance) {
            if ($currencyBalance->currency_code !== $primaryCurrency) {
                $balances[$currencyBalance->currency_code] = [
                    'currency_code' => $currencyBalance->currency_code,
                    'balance' => (float) $currencyBalance->balance,
                    'pending_balance' => (float) $currencyBalance->pending_balance,
                    'total_balance' => (float) $currencyBalance->total_balance,
                    'is_primary' => false
                ];
            }
        }

        return $balances;
    }

    /**
     * Enable multi-currency support for this account
     */
    public function enableMultiCurrency(array $secondaryCurrencies = []): void
    {
        $this->is_multi_currency = true;
        $this->secondary_currencies = $secondaryCurrencies;
        $this->save();

        // Create currency balance records for secondary currencies
        foreach ($secondaryCurrencies as $currencyCode) {
            CurrencyBalance::findOrCreate(
                $this->id,
                $currencyCode,
                $this->team_id,
                $this->user_id
            );
        }
    }

    /**
     * Add a secondary currency to this account
     */
    public function addSecondaryCurrency(string $currencyCode): void
    {
        if (!$this->isMultiCurrency()) {
            $this->enableMultiCurrency([$currencyCode]);
            return;
        }

        $secondaryCurrencies = $this->getSecondaryCurrencies();
        
        if (!in_array($currencyCode, $secondaryCurrencies)) {
            $secondaryCurrencies[] = $currencyCode;
            $this->secondary_currencies = $secondaryCurrencies;
            $this->save();

            // Create currency balance record
            CurrencyBalance::findOrCreate(
                $this->id,
                $currencyCode,
                $this->team_id,
                $this->user_id
            );
        }
    }

    /**
     * Remove a secondary currency from this account
     */
    public function removeSecondaryCurrency(string $currencyCode): void
    {
        $secondaryCurrencies = $this->getSecondaryCurrencies();
        $updatedCurrencies = array_filter($secondaryCurrencies, fn($currency) => $currency !== $currencyCode);
        
        $this->secondary_currencies = array_values($updatedCurrencies);
        
        // If no secondary currencies left, disable multi-currency
        if (empty($updatedCurrencies)) {
            $this->is_multi_currency = false;
        }
        
        $this->save();

        // Remove currency balance record
        $this->currencyBalances()
            ->where('currency_code', $currencyCode)
            ->delete();
    }

    /**
     * Check if a currency is supported by this account
     */
    public function supportsCurrency(string $currencyCode): bool
    {
        // Primary currency is always supported
        if ($currencyCode === $this->getPrimaryCurrency()) {
            return true;
        }
        
        // If not multi-currency, only primary currency is supported
        if (!$this->isMultiCurrency()) {
            return false;
        }
        
        // For multi-currency accounts, check if currency is in secondary currencies
        // OR if no secondary currencies are defined, allow any currency (dynamic support)
        $secondaryCurrencies = $this->getSecondaryCurrencies();
        
        if (empty($secondaryCurrencies)) {
            // If multi-currency is enabled but no specific currencies defined,
            // we allow any currency and will auto-add it when used
            return true;
        }
        
        return in_array($currencyCode, $secondaryCurrencies);
    }

    /**
     * Get or create currency balance record
     */
    public function getOrCreateCurrencyBalance(string $currencyCode): CurrencyBalance
    {
        return CurrencyBalance::findOrCreate(
            $this->id,
            $currencyCode,
            $this->team_id,
            $this->user_id
        );
    }
}
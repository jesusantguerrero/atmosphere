<?php

namespace App\Models;

use App\Models\CurrencyBalance;
use Insane\Journal\Models\Core\Account as BaseAccount;
use Insane\Journal\Models\Core\AccountDetailType;

class Account extends BaseAccount
{
    /**
     * Override the vendor's static finder so it returns App\Models\Account
     * instances (with our $appends) instead of vendor base-class instances.
     *
     * The vendor implementation does `return Account::where(...)` with a
     * hard-coded import to the vendor class, which bypasses LSB. Using
     * `static::` here makes the query resolve to whichever class called it —
     * App\Models\Account when called via App\Models\Account::getByDetailTypes(),
     * so the all_currency_balances accessor lands in the serialized JSON
     * (sidebar accounts in HandleInertiaRequests, DashboardController, etc.).
     */
    public static function getByDetailTypes($teamId, $detailTypes = AccountDetailType::ALL)
    {
        return static::where('accounts.team_id', $teamId)
            ->byDetailTypes($detailTypes)
            ->orderBy('accounts.index')
            ->with(['reconciliationLast'])
            ->get();
    }

    /**
     * Initialize the model and merge fillable / casts / appends with parent.
     *
     * NOTE: declaring `protected $appends = ['all_currency_balances']` at the
     * class level would OVERRIDE the vendor's `$appends = ['balance']` (PHP
     * does not auto-merge inherited properties) and the UI would lose the
     * `balance` field that powers the account header. Merge here instead so
     * both the vendor's `balance` and our `all_currency_balances` ship in the
     * serialized payload.
     */
    public function __construct(array $attributes = [])
    {
        $this->fillable = array_merge(
            $this->fillable ?? [],
            [
                'is_multi_currency',
                'secondary_currencies',
            ]
        );

        $this->casts = array_merge(
            $this->casts ?? [],
            [
                'is_multi_currency' => 'boolean',
                'secondary_currencies' => 'array',
            ]
        );

        $this->appends = array_values(array_unique(array_merge(
            $this->appends ?? [],
            ['all_currency_balances'],
        )));

        parent::__construct($attributes);
    }

    /**
     * Accessor that powers the `MultiCurrencyDetailPanel` Vue component on
     * `Pages/Finance/Account.vue`. Returns null for single-currency accounts so
     * the panel hides itself; returns the same shape as `getAllCurrencyBalances()`
     * for multi-currency ones (DOP/USD/etc. side-by-side, BHD-style).
     */
    public function getAllCurrencyBalancesAttribute(): ?array
    {
        if (!$this->isMultiCurrency()) {
            return null;
        }

        return array_values($this->getAllCurrencyBalances());
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
     * Get all currency balances for this account.
     *
     * Returns one row per supported currency. The primary currency row is
     * always present. Secondary rows come from either the `currency_balances`
     * table (when populated by MultiCurrencyTransactionService) OR — as a
     * fallback — from `secondary_currencies` on the account itself with zero
     * balances. The fallback matters when the user enables multi-currency via
     * AccountUpdate (raw update) without going through enableMultiCurrency(),
     * which would normally seed the CurrencyBalance rows. Without it the
     * MultiCurrencyDetailPanel would show only the primary column and feel
     * broken right after the user flips the toggle.
     */
    public function getAllCurrencyBalances(): array
    {
        $balances = [];
        $primaryCurrency = $this->getPrimaryCurrency();

        $balances[$primaryCurrency] = [
            'currency_code' => $primaryCurrency,
            'balance' => $this->getBalanceInCurrency($primaryCurrency),
            'pending_balance' => $this->getPendingBalanceInCurrency($primaryCurrency),
            'total_balance' => $this->getTotalBalanceInCurrency($primaryCurrency),
            'is_primary' => true,
        ];

        foreach ($this->currencyBalances as $currencyBalance) {
            if ($currencyBalance->currency_code !== $primaryCurrency) {
                $balances[$currencyBalance->currency_code] = [
                    'currency_code' => $currencyBalance->currency_code,
                    'balance' => (float) $currencyBalance->balance,
                    'pending_balance' => (float) $currencyBalance->pending_balance,
                    'total_balance' => (float) $currencyBalance->total_balance,
                    'is_primary' => false,
                ];
            }
        }

        foreach ($this->getSecondaryCurrencies() as $secondaryCurrency) {
            if ($secondaryCurrency === $primaryCurrency) {
                continue;
            }

            if (! isset($balances[$secondaryCurrency])) {
                $balances[$secondaryCurrency] = [
                    'currency_code' => $secondaryCurrency,
                    'balance' => 0.0,
                    'pending_balance' => 0.0,
                    'total_balance' => 0.0,
                    'is_primary' => false,
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
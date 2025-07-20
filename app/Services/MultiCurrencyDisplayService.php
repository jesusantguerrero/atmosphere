<?php

namespace App\Services;

use App\Domains\Transaction\Models\Transaction;
use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;

class MultiCurrencyDisplayService
{
    /**
     * Enhance transaction data with multi-currency display information
     * 
     * @param Transaction|Collection $transactions
     * @return array|Collection
     */
    public function enhanceTransactionDisplay($transactions)
    {
        if ($transactions instanceof Collection) {
            return $transactions->map(function ($transaction) {
                return $this->enhanceSingleTransaction($transaction);
            });
        }
        
        return $this->enhanceSingleTransaction($transactions);
    }
    
    /**
     * Enhance a single transaction with multi-currency display information
     * 
     * @param Transaction $transaction
     * @return Transaction
     */
    private function enhanceSingleTransaction(Transaction $transaction): Transaction
    {
        // Add multi-currency display attributes
        $transaction->setAttribute('display_currencies', $this->getDisplayCurrencies($transaction));
        $transaction->setAttribute('is_multi_currency_transaction', $this->isMultiCurrencyTransaction($transaction));
        $transaction->setAttribute('conversion_info', $this->getConversionInfo($transaction));
        
        return $transaction;
    }
    
    /**
     * Get display currencies for a transaction
     * 
     * @param Transaction $transaction
     * @return array
     */
    private function getDisplayCurrencies(Transaction $transaction): array
    {
        $currencies = [];
        
        // Always show the transaction currency
        $currencies['transaction'] = [
            'code' => $transaction->currency_code ?? $transaction->account->getPrimaryCurrency(),
            'amount' => (float) $transaction->total,
            'formatted_amount' => $this->formatCurrency($transaction->total, $transaction->currency_code ?? $transaction->account->getPrimaryCurrency()),
            'is_primary' => ($transaction->currency_code ?? $transaction->account->getPrimaryCurrency()) === $transaction->account->getPrimaryCurrency(),
        ];
        
        // If there's a conversion, show the primary currency amount
        if ($transaction->exchange_rate && $transaction->exchange_amount) {
            $primaryCurrency = $transaction->account->getPrimaryCurrency();
            $currencies['converted'] = [
                'code' => $primaryCurrency,
                'amount' => (float) $transaction->exchange_amount,
                'formatted_amount' => $this->formatCurrency($transaction->exchange_amount, $primaryCurrency),
                'is_primary' => true,
                'exchange_rate' => (float) $transaction->exchange_rate,
            ];
        }
        
        return $currencies;
    }
    
    /**
     * Check if this is a multi-currency transaction
     * 
     * @param Transaction $transaction
     * @return bool
     */
    private function isMultiCurrencyTransaction(Transaction $transaction): bool
    {
        if (!$transaction->account->isMultiCurrency()) {
            return false;
        }
        
        $transactionCurrency = $transaction->currency_code ?? $transaction->account->getPrimaryCurrency();
        return $transactionCurrency !== $transaction->account->getPrimaryCurrency();
    }
    
    /**
     * Get conversion information for display
     * 
     * @param Transaction $transaction
     * @return array|null
     */
    private function getConversionInfo(Transaction $transaction): ?array
    {
        if (!$transaction->exchange_rate || !$transaction->exchange_amount) {
            return null;
        }
        
        return [
            'has_conversion' => true,
            'exchange_rate' => (float) $transaction->exchange_rate,
            'original_amount' => (float) $transaction->total,
            'converted_amount' => (float) $transaction->exchange_amount,
            'original_currency' => $transaction->currency_code,
            'converted_currency' => $transaction->account->getPrimaryCurrency(),
            'conversion_display' => $this->formatConversionDisplay($transaction),
        ];
    }
    
    /**
     * Format conversion display string
     * 
     * @param Transaction $transaction
     * @return string
     */
    private function formatConversionDisplay(Transaction $transaction): string
    {
        $originalAmount = $this->formatCurrency($transaction->total, $transaction->currency_code);
        $convertedAmount = $this->formatCurrency($transaction->exchange_amount, $transaction->account->getPrimaryCurrency());
        $rate = number_format($transaction->exchange_rate, 4);
        
        return "{$originalAmount} → {$convertedAmount} (Rate: {$rate})";
    }
    
    /**
     * Format currency amount for display
     * 
     * @param float $amount
     * @param string $currencyCode
     * @return string
     */
    private function formatCurrency(float $amount, string $currencyCode): string
    {
        // Basic currency formatting - can be enhanced with proper locale formatting
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'DOP' => 'RD$',
            'CAD' => 'C$',
            'AUD' => 'A$',
        ];
        
        $symbol = $symbols[$currencyCode] ?? $currencyCode . ' ';
        return $symbol . number_format($amount, 2);
    }
    
    /**
     * Get account currency balances with formatting
     * 
     * @param Account $account
     * @return array
     */
    public function getFormattedCurrencyBalances(Account $account): array
    {
        $balances = $account->getAllCurrencyBalances();
        
        foreach ($balances as $currencyCode => &$balance) {
            $balance['formatted_balance'] = $this->formatCurrency($balance['balance'], $currencyCode);
            $balance['formatted_pending_balance'] = $this->formatCurrency($balance['pending_balance'], $currencyCode);
            $balance['formatted_total_balance'] = $this->formatCurrency($balance['total_balance'], $currencyCode);
        }
        
        return $balances;
    }
    
    /**
     * Get summary of multi-currency activity for an account
     * 
     * @param Account $account
     * @param string|null $period
     * @return array
     */
    public function getMultiCurrencyActivitySummary(Account $account, ?string $period = null): array
    {
        if (!$account->isMultiCurrency()) {
            return [];
        }
        
        $query = $account->transactions();
        
        // Apply period filter if specified
        if ($period) {
            $startDate = match($period) {
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                'quarter' => now()->startOfQuarter(),
                'year' => now()->startOfYear(),
                default => now()->startOfMonth(),
            };
            
            $query->where('date', '>=', $startDate);
        }
        
        $transactions = $query->get();
        
        $summary = [
            'total_transactions' => $transactions->count(),
            'multi_currency_transactions' => 0,
            'currencies_used' => [],
            'conversion_summary' => [],
        ];
        
        foreach ($transactions as $transaction) {
            $transactionCurrency = $transaction->currency_code ?? $account->getPrimaryCurrency();
            
            // Track currencies used
            if (!isset($summary['currencies_used'][$transactionCurrency])) {
                $summary['currencies_used'][$transactionCurrency] = [
                    'count' => 0,
                    'total_amount' => 0,
                ];
            }
            
            $summary['currencies_used'][$transactionCurrency]['count']++;
            $summary['currencies_used'][$transactionCurrency]['total_amount'] += $transaction->total;
            
            // Track multi-currency transactions
            if ($transactionCurrency !== $account->getPrimaryCurrency()) {
                $summary['multi_currency_transactions']++;
                
                // Track conversions
                if ($transaction->exchange_rate && $transaction->exchange_amount) {
                    $conversionKey = $transactionCurrency . '_to_' . $account->getPrimaryCurrency();
                    
                    if (!isset($summary['conversion_summary'][$conversionKey])) {
                        $summary['conversion_summary'][$conversionKey] = [
                            'count' => 0,
                            'total_original' => 0,
                            'total_converted' => 0,
                            'avg_rate' => 0,
                        ];
                    }
                    
                    $summary['conversion_summary'][$conversionKey]['count']++;
                    $summary['conversion_summary'][$conversionKey]['total_original'] += $transaction->total;
                    $summary['conversion_summary'][$conversionKey]['total_converted'] += $transaction->exchange_amount;
                }
            }
        }
        
        // Calculate average rates
        foreach ($summary['conversion_summary'] as &$conversion) {
            if ($conversion['total_original'] > 0) {
                $conversion['avg_rate'] = $conversion['total_converted'] / $conversion['total_original'];
            }
        }
        
        return $summary;
    }
}
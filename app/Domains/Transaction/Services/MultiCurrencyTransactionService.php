<?php

namespace App\Domains\Transaction\Services;

use App\Models\Account;
use App\Models\CurrencyBalance;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class MultiCurrencyTransactionService
{
    /**
     * Record a transaction in a secondary currency without conversion
     * This creates a pending charge on the credit card in the transaction currency
     * 
     * @param array $transactionData Transaction data including currency information
     * @return Transaction The created transaction
     * @throws InvalidArgumentException If account doesn't support the currency
     * @throws RuntimeException If transaction creation fails
     */
    public function recordSecondaryTransaction(array $transactionData): Transaction
    {
        // Validate required fields
        $this->validateTransactionData($transactionData);
        
        $account = Account::findOrFail($transactionData['account_id']);
        $transactionCurrency = $transactionData['currency_code'];
        
        // Ensure account supports multi-currency and the specific currency
        if (!$account->isMultiCurrency()) {
            throw new InvalidArgumentException("Account {$account->name} does not support multi-currency transactions");
        }
        
        if (!$account->supportsCurrency($transactionCurrency)) {
            throw new InvalidArgumentException("Account {$account->name} does not support currency {$transactionCurrency}");
        }
        
        // If this is a new currency for a multi-currency account, auto-add it
        if ($transactionCurrency !== $account->getPrimaryCurrency() && 
            !in_array($transactionCurrency, $account->getSecondaryCurrencies())) {
            $account->addSecondaryCurrency($transactionCurrency);
        }
        
        DB::beginTransaction();
        
        try {
            // Create the transaction in the original currency
            $transaction = Transaction::create($transactionData);
            
            // Update pending balance for the transaction currency
            $this->updatePendingBalance($account, $transactionCurrency, $transactionData['total']);
            
            DB::commit();
            
            return $transaction;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new RuntimeException("Failed to record secondary currency transaction: " . $e->getMessage());
        }
    }
    
    /**
     * Process a credit card payment with currency conversion
     * This converts pending secondary currency charges to the account's primary currency
     * 
     * @param Account $account The credit card account
     * @param float $total The amount in secondary currency being paid
     * @param float $exchangeAmount The equivalent amount in primary currency
     * @param string $secondaryCurrency The secondary currency code
     * @param Carbon $paymentDate The date of payment
     * @param array $additionalData Additional transaction data
     * @return Transaction The payment transaction
     * @throws InvalidArgumentException If parameters are invalid
     * @throws RuntimeException If payment processing fails
     */
    public function processCreditCardPayment(
        Account $account,
        float $total,
        float $exchangeAmount,
        string $secondaryCurrency,
        Carbon $paymentDate,
        array $additionalData = []
    ): Transaction {
        // Validate inputs
        if ($total <= 0 || $exchangeAmount <= 0) {
            throw new InvalidArgumentException('Payment amounts must be positive');
        }
        
        if (!$account->supportsCurrency($secondaryCurrency)) {
            throw new InvalidArgumentException("Account does not support currency {$secondaryCurrency}");
        }
        
        // If this is a new currency for a multi-currency account, auto-add it
        if ($secondaryCurrency !== $account->getPrimaryCurrency() && 
            !in_array($secondaryCurrency, $account->getSecondaryCurrencies())) {
            $account->addSecondaryCurrency($secondaryCurrency);
        }
        
        // Calculate exchange rate
        $exchangeRate = $this->calculateExchangeRate($total, $exchangeAmount);
        
        DB::beginTransaction();
        
        try {
            // Create payment transaction with conversion data
            $paymentData = array_merge($additionalData, [
                'account_id' => $account->id,
                'total' => $total,
                'exchange_amount' => $exchangeAmount,
                'exchange_rate' => $exchangeRate,
                'currency_code' => $secondaryCurrency,
                'date' => $paymentDate,
                'description' => $additionalData['description'] ?? "Credit card payment - {$secondaryCurrency} to {$account->getPrimaryCurrency()}",
                'team_id' => $account->team_id,
                'user_id' => $account->user_id,
            ]);
            
            $transaction = Transaction::create($paymentData);
            
            // Process currency conversion and balance updates
            $this->processCurrencyConversion($account, $secondaryCurrency, $total, $exchangeAmount, $exchangeRate);
            
            DB::commit();
            
            return $transaction;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new RuntimeException("Failed to process credit card payment: " . $e->getMessage());
        }
    }
    
    /**
     * Calculate exchange rate from total and exchange amount
     * 
     * @param float $total Amount in secondary currency
     * @param float $exchangeAmount Amount in primary currency
     * @return float The calculated exchange rate
     * @throws InvalidArgumentException If total is zero
     */
    public function calculateExchangeRate(float $total, float $exchangeAmount): float
    {
        if ($total == 0) {
            throw new InvalidArgumentException('Total amount cannot be zero for exchange rate calculation');
        }
        
        return round($exchangeAmount / $total, 6);
    }
    
    /**
     * Get pending balance for a specific currency on an account
     * 
     * @param Account $account The account to check
     * @param string $currencyCode The currency code
     * @return float The pending balance amount
     */
    public function getPendingBalanceInCurrency(Account $account, string $currencyCode): float
    {
        return $account->getPendingBalanceInCurrency($currencyCode);
    }
    
    /**
     * Get all pending balances for an account
     * 
     * @param Account $account The account to check
     * @return array Array of currency codes and their pending balances
     */
    public function getAllPendingBalances(Account $account): array
    {
        $balances = [];
        
        // Get pending balances from currency_balances table
        $currencyBalances = $account->currencyBalances()
            ->where('pending_balance', '!=', 0)
            ->get();
            
        foreach ($currencyBalances as $currencyBalance) {
            $balances[$currencyBalance->currency_code] = (float) $currencyBalance->pending_balance;
        }
        
        return $balances;
    }
    
    /**
     * Update currency balances when transactions are processed
     * 
     * @param Account $account The account to update
     * @param string $currencyCode The currency code
     * @param float $amount The amount to add to balance
     * @param bool $isPending Whether this is a pending balance update
     * @return void
     */
    public function updateCurrencyBalance(Account $account, string $currencyCode, float $amount, bool $isPending = false): void
    {
        $currencyBalance = $account->getOrCreateCurrencyBalance($currencyCode);
        $currencyBalance->updateBalance($amount, $isPending);
    }
    
    /**
     * Process payment-time currency conversion
     * This reduces pending secondary currency balance and updates primary currency balance
     * 
     * @param Account $account The credit card account
     * @param string $secondaryCurrency The secondary currency being converted
     * @param float $secondaryAmount The amount in secondary currency
     * @param float $primaryAmount The equivalent amount in primary currency
     * @param float $exchangeRate The exchange rate used
     * @return array Conversion details
     */
    protected function processCurrencyConversion(
        Account $account,
        string $secondaryCurrency,
        float $secondaryAmount,
        float $primaryAmount,
        float $exchangeRate
    ): array {
        $primaryCurrency = $account->getPrimaryCurrency();
        
        // Get current pending balance in secondary currency
        $currentPendingBalance = $this->getPendingBalanceInCurrency($account, $secondaryCurrency);
        
        if ($currentPendingBalance < $secondaryAmount) {
            throw new InvalidArgumentException(
                "Payment amount ({$secondaryAmount}) exceeds pending balance ({$currentPendingBalance}) in {$secondaryCurrency}"
            );
        }
        
        // Reduce pending balance in secondary currency
        $this->updateCurrencyBalance($account, $secondaryCurrency, -$secondaryAmount, true);
        
        // Update primary currency balance (for credit cards, this reduces the debt)
        $this->updateCurrencyBalance($account, $primaryCurrency, -$primaryAmount, false);
        
        return [
            'secondary_currency' => $secondaryCurrency,
            'secondary_amount' => $secondaryAmount,
            'primary_currency' => $primaryCurrency,
            'primary_amount' => $primaryAmount,
            'exchange_rate' => $exchangeRate,
            'conversion_date' => now(),
        ];
    }
    
    /**
     * Update pending balance for a currency
     * 
     * @param Account $account The account to update
     * @param string $currencyCode The currency code
     * @param float $amount The amount to add to pending balance
     * @return void
     */
    protected function updatePendingBalance(Account $account, string $currencyCode, float $amount): void
    {
        $this->updateCurrencyBalance($account, $currencyCode, $amount, true);
    }
    
    /**
     * Validate transaction data for required fields
     * 
     * @param array $transactionData The transaction data to validate
     * @return void
     * @throws InvalidArgumentException If required fields are missing
     */
    protected function validateTransactionData(array $transactionData): void
    {
        $requiredFields = ['account_id', 'total', 'currency_code', 'team_id', 'user_id'];
        
        foreach ($requiredFields as $field) {
            if (!isset($transactionData[$field])) {
                throw new InvalidArgumentException("Required field '{$field}' is missing from transaction data");
            }
        }
        
        if ($transactionData['total'] <= 0) {
            throw new InvalidArgumentException('Transaction total must be positive');
        }
    }
    
    /**
     * Get payment currency impact for reporting
     * This calculates how a payment affects different currency balances
     * 
     * @param Transaction $payment The payment transaction
     * @param string $fromCurrency The source currency
     * @param string $toCurrency The target currency
     * @param float $exchangeRate The exchange rate used
     * @return array Impact details
     */
    public function calculatePaymentCurrencyImpact(
        Transaction $payment,
        string $fromCurrency,
        string $toCurrency,
        float $exchangeRate
    ): array {
        return [
            'payment_id' => $payment->id,
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'original_amount' => $payment->total,
            'converted_amount' => $payment->exchange_amount,
            'exchange_rate' => $exchangeRate,
            'payment_date' => $payment->date,
            'impact_summary' => [
                $fromCurrency => -$payment->total, // Reduces pending balance
                $toCurrency => -$payment->exchange_amount, // Reduces primary balance
            ]
        ];
    }
    
    /**
     * Create a transaction in secondary currency without immediate conversion
     * This is the main method for recording credit card transactions in foreign currencies
     * 
     * @param array $transactionData Complete transaction data
     * @return Transaction The created transaction
     */
    public function createCreditCardTransaction(array $transactionData): Transaction
    {
        return $this->recordSecondaryTransaction($transactionData);
    }
}
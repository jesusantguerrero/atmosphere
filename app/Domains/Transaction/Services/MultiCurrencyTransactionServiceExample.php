<?php

namespace App\Domains\Transaction\Services;

use App\Models\Account;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Carbon;

/**
 * Example usage of MultiCurrencyTransactionService
 * This demonstrates how the service would be used in real scenarios
 */
class MultiCurrencyTransactionServiceExample
{
    protected MultiCurrencyTransactionService $service;

    public function __construct()
    {
        $this->service = new MultiCurrencyTransactionService();
    }

    /**
     * Example: Recording a credit card transaction in Dominican Pesos
     * This creates a pending charge without immediate conversion
     */
    public function recordDominicanPurchase(Account $creditCardAccount): Transaction
    {
        $transactionData = [
            'account_id' => $creditCardAccount->id,
            'total' => 1500.00, // 1,500 DOP
            'currency_code' => 'DOP',
            'description' => 'Restaurant purchase in Santo Domingo',
            'date' => Carbon::now(),
            'team_id' => $creditCardAccount->team_id,
            'user_id' => $creditCardAccount->user_id,
            'payee_id' => 1, // Restaurant payee
            'category_id' => 5, // Dining category
        ];

        // This records the transaction in DOP and updates pending balance
        return $this->service->recordSecondaryTransaction($transactionData);
    }

    /**
     * Example: Processing credit card payment with currency conversion
     * This converts pending DOP charges to USD at payment time
     */
    public function processCreditCardPayment(Account $creditCardAccount): Transaction
    {
        // Payment details
        $dopAmount = 1500.00; // Amount in Dominican Pesos
        $usdAmount = 27.75;   // Equivalent amount in US Dollars (from bank/payment processor)
        
        return $this->service->processCreditCardPayment(
            $creditCardAccount,
            $dopAmount,
            $usdAmount,
            'DOP', // Secondary currency being paid
            Carbon::now(),
            [
                'description' => 'Credit card payment - DOP charges converted to USD',
                'payee_id' => 2, // Bank/payment processor
                'category_id' => 10, // Transfer/payment category
            ]
        );
    }

    /**
     * Example: Getting pending balances for reporting
     */
    public function getCreditCardSummary(Account $creditCardAccount): array
    {
        return [
            'account_name' => $creditCardAccount->name,
            'primary_currency' => $creditCardAccount->getPrimaryCurrency(),
            'secondary_currencies' => $creditCardAccount->getSecondaryCurrencies(),
            'pending_balances' => $this->service->getAllPendingBalances($creditCardAccount),
            'current_balances' => $creditCardAccount->getAllCurrencyBalances(),
        ];
    }

    /**
     * Example: Calculate exchange rate from actual payment amounts
     */
    public function calculateRateFromPayment(float $foreignAmount, float $domesticAmount): array
    {
        $exchangeRate = $this->service->calculateExchangeRate($foreignAmount, $domesticAmount);
        
        return [
            'foreign_amount' => $foreignAmount,
            'domestic_amount' => $domesticAmount,
            'exchange_rate' => $exchangeRate,
            'rate_description' => "1 unit of foreign currency = {$exchangeRate} units of domestic currency",
            'conversion_example' => [
                'foreign' => 100,
                'domestic' => 100 * $exchangeRate,
            ]
        ];
    }

    /**
     * Example: Complete workflow from transaction to payment
     */
    public function completeWorkflowExample(Account $creditCardAccount): array
    {
        $results = [];
        
        // Step 1: Record a transaction in secondary currency
        $transaction = $this->recordDominicanPurchase($creditCardAccount);
        $results['transaction_recorded'] = [
            'id' => $transaction->id,
            'amount' => $transaction->total,
            'currency' => $transaction->currency_code,
        ];
        
        // Step 2: Check pending balances
        $pendingBalances = $this->service->getAllPendingBalances($creditCardAccount);
        $results['pending_balances_after_transaction'] = $pendingBalances;
        
        // Step 3: Process payment with conversion
        $payment = $this->processCreditCardPayment($creditCardAccount);
        $results['payment_processed'] = [
            'id' => $payment->id,
            'original_amount' => $payment->total,
            'converted_amount' => $payment->exchange_amount,
            'exchange_rate' => $payment->exchange_rate,
        ];
        
        // Step 4: Check balances after payment
        $finalBalances = $this->service->getAllPendingBalances($creditCardAccount);
        $results['pending_balances_after_payment'] = $finalBalances;
        
        // Step 5: Calculate payment impact
        $impact = $this->service->calculatePaymentCurrencyImpact(
            $payment,
            'DOP',
            'USD',
            $payment->exchange_rate
        );
        $results['payment_impact'] = $impact;
        
        return $results;
    }
}
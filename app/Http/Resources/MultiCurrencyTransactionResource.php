<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MultiCurrencyTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'total' => (float) $this->total,
            'currency_code' => $this->currency_code,
            'date' => $this->date,
            'direction' => $this->direction,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Account information
            'account' => [
                'id' => $this->account->id,
                'name' => $this->account->name,
                'currency_code' => $this->account->currency_code,
                'is_multi_currency' => $this->account->isMultiCurrency(),
                'primary_currency' => $this->account->getPrimaryCurrency(),
                'secondary_currencies' => $this->account->getSecondaryCurrencies(),
            ],
            
            // Counter account information
            'counter_account' => $this->when($this->counterAccount, [
                'id' => $this->counterAccount?->id,
                'name' => $this->counterAccount?->name,
                'currency_code' => $this->counterAccount?->currency_code,
            ]),
            
            // Category information
            'category' => $this->when($this->category, [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ]),
            
            // Payee information
            'payee' => $this->when($this->payee, [
                'id' => $this->payee?->id,
                'name' => $this->payee?->name,
            ]),
            
            // Multi-currency specific information
            'multi_currency' => [
                'is_converted' => !is_null($this->exchange_rate) && !is_null($this->exchange_amount),
                'exchange_rate' => $this->when($this->exchange_rate, (float) $this->exchange_rate),
                'exchange_amount' => $this->when($this->exchange_amount, (float) $this->exchange_amount),
                'primary_currency_amount' => $this->when($this->exchange_amount, (float) $this->exchange_amount),
                'secondary_currency_amount' => (float) $this->total,
                'is_secondary_currency' => $this->currency_code !== $this->account->getPrimaryCurrency(),
                'display_currencies' => $this->getDisplayCurrencies(),
            ],
        ];
    }
    
    /**
     * Get display currencies for the transaction
     * 
     * @return array
     */
    private function getDisplayCurrencies(): array
    {
        $currencies = [];
        
        // Always show the transaction currency
        $currencies['transaction'] = [
            'code' => $this->currency_code,
            'amount' => (float) $this->total,
            'is_primary' => $this->currency_code === $this->account->getPrimaryCurrency(),
        ];
        
        // If there's a conversion, show the primary currency amount
        if ($this->exchange_rate && $this->exchange_amount) {
            $currencies['converted'] = [
                'code' => $this->account->getPrimaryCurrency(),
                'amount' => (float) $this->exchange_amount,
                'is_primary' => true,
                'exchange_rate' => (float) $this->exchange_rate,
            ];
        }
        
        return $currencies;
    }
}
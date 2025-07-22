<?php

namespace App\Domains\Transaction\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'number' => $this->number,
            'direction' => $this->direction,
            'payee' => $this->payee,
            'description' => $this->description,
            'direction' => $this->direction,
            'account' => $this->mainLine ? $this->mainLine->account : [],
            'counterAccount' => $this->counterAccount ?? [],
            'category' => $this->category,
            'currency_code' => $this->currency_code,
            'total' => $this->total,
            'lines' => $this->lines,
            'status' => $this->status,
            'mainLine' => $this->mainLine,
            'schedule' => $this->schedule,
            'linked' => $this->whenLoaded('linked', function () {
                return $this->linked->toArray();
            }),
            
            // Multi-currency display information (if enhanced by MultiCurrencyDisplayService)
            'display_currencies' => $this->when(
                isset($this->display_currencies), 
                $this->display_currencies
            ),
            'is_multi_currency_transaction' => $this->when(
                isset($this->is_multi_currency_transaction), 
                $this->is_multi_currency_transaction
            ),
            'conversion_info' => $this->when(
                isset($this->conversion_info), 
                $this->conversion_info
            ),
            
            // Exchange rate information for converted transactions
            'exchange_rate' => $this->when($this->exchange_rate, (float) $this->exchange_rate),
            'exchange_amount' => $this->when($this->exchange_amount, (float) $this->exchange_amount),
        ];
    }
}

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
            'account' => $this->mainLine ? $this->mainLine->account: [],
            'category' => $this->category ? $this->category->account : [],
            'transactionCategory' => $this->transactionCategory,
            'currency_code' => $this->currency_code,
            'total' => $this->total,
            'lines' => $this->lines,
            'status' => $this->status,
            'mainLine' => $this->mainLine,
            'schedule' => $this->schedule,
            'linked' => $this->whenLoaded('linked', function() {
                return $this->linked->toArray();
            })
        ];
    }
}

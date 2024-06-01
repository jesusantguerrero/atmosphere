<?php

namespace App\Listeners;

use Illuminate\Support\Str;
use Freesgen\Atmosphere\Models\Label;
use Illuminate\Contracts\Queue\ShouldQueue;
use Insane\Journal\Events\TransactionUpdated;
use App\Domains\Transaction\Models\TransactionLine;

class HandleTransactionUpdated implements ShouldQueue
{
    public function handle(TransactionUpdated  $event)
    {
        $transaction = $event->transaction;
        $transactionData = $event->transactionData;

        $labelId = $transactionData["label_id"] ?? null;
        $isNewLabel = Str::contains($labelId, "new::");
        $newLabelData = [
            "user_id" => $transaction->user_id,
            "team_id" => $transaction->team_id,
        ];

        if (!isset($transactionData["label_id"]) && $transactionData["counter_account_id"]) {
            $label = Label::firstOrCreate([
                ...$newLabelData,
                "name" => $transactionData["label_name"],
                "labelable_type" => TransactionLine::class
            ]);
            $labelId = $label->id;
        } else if ($labelId == 'new' || $isNewLabel) {
            $labelName = $transactionData['label_name'] ?? trim(Str::replace('new::', '', $transactionData["label_id"])) ?? null;
            if ($labelName) {
                $label = Label::firstOrCreate([
                    ...$newLabelData,
                    "name" => $labelName,
                    "labelable_type" => TransactionLine::class
                ]);
                $labelId = $label->id;
                $transactionData["label_id"] = $labelId;
            }
        }
    }
}

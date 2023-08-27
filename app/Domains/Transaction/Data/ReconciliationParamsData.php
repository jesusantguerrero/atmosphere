<?php
namespace App\Domains\Transaction\Data;

use Spatie\LaravelData\Data;

class ReconciliationParamsData extends Data {
    public function __construct(
        public int $account_id,
        public float $balance,
        public string $date,
        public int $user_id,
    ) {

    }
}

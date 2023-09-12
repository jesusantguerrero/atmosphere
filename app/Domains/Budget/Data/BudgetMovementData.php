<?php
namespace App\Domains\Budget\Data;

use Spatie\LaravelData\Data;

class BudgetMovementData extends Data {
    public function __construct(
        public ?int $id,
        public int $team_id,
        public int  $user_id,
        public ?int $source_category_id,
        public int $destination_category_id,
        public ?string $type = "movement",
        public string $date,
        public float $amount,
    ) {

    }
}

<?php
namespace App\Domains\Budget\Data;

use Spatie\LaravelData\Data;

class BudgetAssignData extends Data {
    public function __construct(
        public int $team_id,
        public int  $user_id,
        public string $date,
        public ?int $category_id,
        public float $amount,
    ) {

    }
}

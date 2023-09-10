<?php
namespace App\Domains\Budget\Data;

use Spatie\LaravelData\Data;

class CategoryData extends Data {
    public function __construct(
        public ?int $id,
        public int $team_id,
        public int  $user_id,
        public ?int $account_id,
        public ?int $parent_id,
        public string $name,
        public CategoryResourceType $resource_type = CategoryResourceType::Transactions
    ) {

    }
}

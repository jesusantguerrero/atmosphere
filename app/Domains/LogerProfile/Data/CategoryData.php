<?php
namespace App\Domains\LogerProfile\Data;

use Spatie\LaravelData\Data;

class CategoryData extends Data {
    public function __construct(
        public ?int $id,
        public int $team_id,
        public int  $user_id,
        public string $name,
    ) {

    }
}

<?php

namespace App\Domains\LogerProfile\Data;

use Spatie\LaravelData\Data;

class AccountData extends Data
{
    public function __construct(
        public ?int $id,
        public int $team_id,
        public int $user_id,
        public string $name,
    ) {

    }
}

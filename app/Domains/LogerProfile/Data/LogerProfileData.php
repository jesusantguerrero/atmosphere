<?php
namespace App\Domains\LogerProfile\Data;

use Spatie\LaravelData\Data;

class LogerProfileData extends Data {
    public function __construct(
        public ?int $id,
        public int $team_id,
        public int  $user_id,
        public string $name,
        public ?string $image_url,
        public ?string $background_url,
        public ?string $cover_url,
        public ?string $description
    ) {

    }
}

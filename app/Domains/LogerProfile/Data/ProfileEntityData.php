<?php
namespace App\Domains\LogerProfile\Data;

use Spatie\LaravelData\Data;

class ProfileEntityData extends Data {
    public function __construct(
        public ?int $id,
        public int $team_id,
        public int  $user_id,
        public int  $profile_id,
        public string  $entity_type,
        public int  $entity_id,
        public string $name,
        public ?string $description,
        public mixed $entity
    ) {

    }


    public static function forVue(mixed $requestData) {
        return  new self(
            null,
            $requestData['team_id'],
            $requestData['user_id'],
            $requestData['profile_id'],
            ProfileEntityType::getClass($requestData['entity_type']),
            $requestData['entity_id'],
            $requestData['name'],
            null,
            null
        );
    }
}

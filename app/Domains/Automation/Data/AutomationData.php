<?php
namespace App\Domains\Automation\Data;

use Spatie\LaravelData\Data;

class AutomationData extends Data {

    public function __construct(
    public int $team_id,
    public int  $user_id,
    public int $service_id,
    public int $integration_id
    ) {

    }
}

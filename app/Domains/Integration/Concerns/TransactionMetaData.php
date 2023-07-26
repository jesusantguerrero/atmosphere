<?php
namespace App\Domains\Integration\Concerns;

use Spatie\LaravelData\Data;

class TransactionMetadata extends Data {
    public string  $resource_id;
    public string $resource_origin;
    public string $resource_type;
}

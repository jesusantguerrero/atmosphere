<?php

namespace App\Domains\Housing\Data;

use Spatie\LaravelData\Data;

class OccurrenceData extends Data
{
    public function __construct(
        public string $name,
        public string $last_date,
        public string $previous_days_count,
        public string $avg_days_passed,
        public string $notify_on_avg,
        public string $notify_on_last_count,
        public array $conditions,
        public string $type,
        public array $log,
        public string $is_active,
    ) {

    }

    public static function fromImport(array $row) {
        return new self(
            $row["name"],
            $row["last_occurrence_at"],
            $row["previous_durationdays"] ?? 0,
            $row["frequency_avg_in_days"] ?? 0,
            $row["notify_on_avg"],
            $row["notify_on_previous_duration"],
            json_decode($row["conditions"], true),
            $row["type"],
            json_decode($row["logs"], true),
            $row["is_active"],
        );
    }
}

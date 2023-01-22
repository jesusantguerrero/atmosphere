<?php

namespace App\Domains\Housing\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class OccurrenceExport implements FromArray
{
    protected $occurrences;

    public function __construct(array $occurrences)
    {
        $this->occurrences = $occurrences;
    }

    public function array(): array
    {
        return $this->occurrences;
    }
}

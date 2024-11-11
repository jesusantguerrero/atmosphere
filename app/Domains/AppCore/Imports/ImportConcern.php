<?php

namespace App\Domains\AppCore\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

abstract class ImportConcern implements SkipsEmptyRows, ToModel, WithChunkReading, WithHeadingRow, WithLimit, WithMapping
{
    use Importable;

    public $session;

    public function __construct($userSession)
    {
        $this->session = [
            'team_id' => $userSession->current_team_id,
            'user_id' => $userSession->id,
        ];
    }

    public function chunkSize(): int
    {
        return config('excel.imports.chunk_size');
    }

    public function limit(): int
    {
        return config('excel.imports.row_limit');
    }
}

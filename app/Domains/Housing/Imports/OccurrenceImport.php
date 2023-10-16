<?php

namespace App\Domains\Housing\Imports;

use App\Models\User;
use App\Domains\Imports\ImportConcern;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Domains\Housing\Data\OccurrenceData;
use App\Domains\Housing\Actions\RegisterOccurrence;

class OccurrenceImport extends ImportConcern implements ShouldQueue
{

    public function __construct(public User $user)
    {
    }
    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return (new RegisterOccurrence)->fromImport($this->user, OccurrenceData::fromImport($row));
    }

    public function map($row): array
    {
        return $row;
    }
}

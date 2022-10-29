<?php

namespace App\Http\Controllers\Housing;

use App\Domains\Housing\Models\OccurrenceCheck;
use Freesgen\Atmosphere\Http\InertiaController;

class OccurrenceController extends InertiaController
{
    public function __construct(OccurrenceCheck $occurrence)
    {
        $this->model = $occurrence;
        $this->templates = [
            'index' => 'Housing/Occurrence',
        ];
        $this->searchable = ["id", "name"];
        $this->includes = [];
        $this->appends = [];
    }
}

<?php

namespace App\Domains\Housing\Contracts;

enum OccurrenceNotifyTypes: string {
    case AVG = 'avg';
    case LAST = 'last';
}

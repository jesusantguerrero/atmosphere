<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;

class RequestQueryHelper {
    public static function getFilterDates($filters) {
        $dates = isset($filters['date']) ? explode("~", $filters['date']) : [
            Carbon::now()->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d')
        ];
        return $dates;
    }
}

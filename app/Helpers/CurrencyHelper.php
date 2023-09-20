<?php

namespace App\Helpers;

use ErnySans\Laraworld\Facades\Countries;

class CurrencyHelper
{
    public static function getAll()
    {
        return collect(Countries::all())->map(function ($country) {
            return [
                'name' => $country->name,
                'code' => $country->currency_code,
                'symbol' => $country->currency,
            ];
        }
        )->filter(function ($curr) {
            return isset($curr['code']);
        })->unique('code')->sortBy('code')->values()->all();
    }
}

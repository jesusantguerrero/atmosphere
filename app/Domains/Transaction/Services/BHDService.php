<?php
namespace App\Domains\Transaction\Services;

class BHDService {
    public static function parseCurrency($bhdCurrencyCode) {
        $BHD_CURRENCY_CODES = [
            'USD' => 'USD',
            'RD' => 'DOP',
        ];
        return $BHD_CURRENCY_CODES[$bhdCurrencyCode];
    }

    public static function parseTypes($type) {
        $types = [
            'Compra' => 1
        ];

        return  $types[$type];
    }
}

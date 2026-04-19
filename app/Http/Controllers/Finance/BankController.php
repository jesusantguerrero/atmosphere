<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Integration\Actions\UniversalBankParser;
use App\Http\Controllers\Controller;

class BankController extends Controller
{
    private const LABELS = [
        'BHD' => 'BHD',
        'APAP' => 'APAP',
        'BSC' => 'BSC',
        'QIK' => 'Qik',
    ];

    public function index()
    {
        return collect(UniversalBankParser::getAllBankPatterns())
            ->map(fn (array $patterns, string $code) => [
                'id' => $code,
                'name' => self::LABELS[$code] ?? $code,
            ])
            ->values();
    }
}

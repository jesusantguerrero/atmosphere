<?php

namespace App\Domains\Integration\Concerns;

use Illuminate\Support\Str;

/**
 * Payee texts that bank emails print where a merchant should be but that name
 * no merchant. They must not become payees, or the category of an unrelated
 * past charge gets inherited through them.
 */
class GenericPayee
{
    private const NAMES = [
        'transaccion aprobada',
        'transaccion rechazada',
        'transferencia lbtr - entrante',
        'transferencia lbtr - saliente',
        'transferencia lbtr entrante',
        'transferencia lbtr saliente',
        'transferencia lbtr',
        'transferencia entrante',
        'transferencia saliente',
        'bhd notification',
        'general provider',
    ];

    public static function matches(?string $name, ?string $accountHolderName = null): bool
    {
        $normalized = self::normalize($name);

        if ($normalized === '' || preg_match('/^[\d\s.,$*-]+$/', $normalized)) {
            return true;
        }

        if ($accountHolderName !== null && $normalized === self::normalize($accountHolderName)) {
            return true;
        }

        return in_array($normalized, self::NAMES, true);
    }

    private static function normalize(?string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', Str::lower(Str::ascii((string) $name))));
    }
}

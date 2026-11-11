<?php

namespace App\Domains\Integration\Services;

use Illuminate\Support\Str;
use Insane\Journal\Models\Core\Account;

/**
 * Finds the team account a bank email talks about from the product's visible
 * last digits. The account name is the user's own wording («Personal: No Debit
 * Card»), so the bank brand in it is only used to break a tie between accounts
 * that share the same last four digits.
 */
class BankAccountMatcher
{
    public static function find(int $teamId, ?string $productCode, ?string $brand = null, int|string|null $excludeAccountId = null): ?Account
    {
        $lastDigits = substr(preg_replace('/\D/', '', (string) $productCode), -4);
        if (strlen($lastDigits) < 4) {
            return null;
        }

        $candidates = Account::where('team_id', $teamId)
            ->when($excludeAccountId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->where(fn ($query) => $query->where('number', $lastDigits)->orWhere('number', 'like', '%'.$lastDigits))
            ->get();

        if ($candidates->count() <= 1) {
            return $candidates->first();
        }

        $brand = trim((string) $brand);
        if ($brand === '') {
            return null;
        }

        $byBrand = $candidates->filter(fn (Account $account) => Str::contains(Str::lower((string) $account->name), Str::lower($brand)));

        return $byBrand->count() === 1 ? $byBrand->first() : null;
    }
}

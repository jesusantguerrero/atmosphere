<?php

namespace App\Domains\Transaction\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;

/**
 * Recognizes a credit-card payment made from a bank account («Pago TC Qik
 * 3861», «PAGO DE TC 4641...1945») and points it at the card, so the card
 * balance drops and the «pay your card» reminder sees it as paid. Without a
 * counter account such a payment is just an uncategorized expense.
 */
class CreditCardPaymentDetector
{
    private const PAYMENT_PATTERN = '/\b(pago|pgo|abono)\b.*\b(tc|t\/c|tdc|tarjeta)\b/iu';

    /**
     * Words of a card name that say nothing about which card it is.
     */
    private const GENERIC_WORDS = [
        'visa', 'master', 'mastercard', 'amex', 'card', 'clasica', 'clásica', 'classic', 'gold', 'platinum',
        'platinium', 'premia', 'black', 'credito', 'crédito', 'tarjeta', 'digital', 'de', 'del', 'la', 'el', 'mi', 'pais', 'país',
    ];

    /**
     * The team credit card this payment goes to, or null when it is not a card
     * payment or no single card fits.
     */
    public static function cardFor(int $teamId, ?string $description, int|string|null $sourceAccountId): ?Account
    {
        if (! $description || ! preg_match(self::PAYMENT_PATTERN, $description)) {
            return null;
        }

        $source = $sourceAccountId ? Account::find($sourceAccountId) : null;
        if (! $source || self::isCreditCard($source)) {
            return null;
        }

        $cards = Account::where('team_id', $teamId)
            ->whereNull('closed_at')
            ->where('id', '!=', $source->id)
            ->where(fn ($query) => $query->whereNotNull('credit_closing_day')->orWhere('credit_limit', '>', 0))
            ->get();

        return self::single(self::byLastDigits($cards, $description)) ?? self::single(self::byName($cards, $description));
    }

    /**
     * Transaction data with the card as counter account when it is a card
     * payment; unchanged otherwise.
     *
     * @param  array<string, mixed>  $transactionData
     * @return array<string, mixed>
     */
    public static function apply(array $transactionData): array
    {
        if (($transactionData['direction'] ?? null) !== Transaction::DIRECTION_CREDIT || ! empty($transactionData['is_transfer'])) {
            return $transactionData;
        }

        $counter = ! empty($transactionData['counter_account_id']) ? Account::find($transactionData['counter_account_id']) : null;
        if ($counter && self::isCreditCard($counter)) {
            return $transactionData;
        }

        $card = self::cardFor((int) $transactionData['team_id'], $transactionData['description'] ?? null, $transactionData['account_id'] ?? null);
        if (! $card) {
            return $transactionData;
        }

        return [
            ...$transactionData,
            'counter_account_id' => $card->id,
            'is_transfer' => true,
            'payee_id' => null,
            'category_id' => null,
        ];
    }

    public static function isCreditCard(Account $account): bool
    {
        return $account->credit_closing_day !== null || (float) $account->credit_limit > 0;
    }

    /**
     * @param  Collection<int, Account>  $cards
     * @return Collection<int, Account>
     */
    private static function byLastDigits(Collection $cards, string $description): Collection
    {
        preg_match_all('/\d{4,}/', $description, $matches);
        $lastDigits = array_map(fn ($digits) => substr($digits, -4), $matches[0]);
        if (! $lastDigits) {
            return collect();
        }

        return $cards->filter(function (Account $card) use ($lastDigits) {
            $own = array_filter([
                substr(preg_replace('/\D/', '', (string) $card->number), -4),
                preg_match('/(\d{4})\D*$/', (string) $card->name, $match) ? $match[1] : null,
            ], fn ($digits) => strlen((string) $digits) === 4);

            return (bool) array_intersect($own, $lastDigits);
        });
    }

    /**
     * @param  Collection<int, Account>  $cards
     * @return Collection<int, Account>
     */
    private static function byName(Collection $cards, string $description): Collection
    {
        $text = ' '.Str::lower(Str::ascii($description)).' ';

        return $cards->filter(function (Account $card) use ($text) {
            $words = preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii((string) $card->name)), -1, PREG_SPLIT_NO_EMPTY);
            $distinctive = array_filter($words, fn ($word) => strlen($word) >= 3 && ! ctype_digit($word) && ! in_array($word, self::GENERIC_WORDS, true));

            return collect($distinctive)->contains(fn ($word) => preg_match('/\b'.preg_quote($word, '/').'\b/', $text));
        });
    }

    /**
     * @param  Collection<int, Account>  $cards
     */
    private static function single(Collection $cards): ?Account
    {
        return $cards->count() === 1 ? $cards->first() : null;
    }
}

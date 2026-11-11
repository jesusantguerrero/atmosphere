<?php

namespace App\Domains\Transaction\Services;

use App\Models\ProcessedEmail;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;

/**
 * A move between two of the user's accounts at different banks arrives as two
 * emails, one per bank. Importing the second one finds the first and turns it
 * into a single transfer instead of recording an expense and an income.
 */
class ImportedTransferPairer
{
    private const DAY_WINDOW = 2;

    /**
     * Convert the one matching opposite movement into a transfer. Returns null
     * (and changes nothing) unless exactly one candidate exists.
     *
     * @param  array<string, mixed>  $transactionData
     */
    public static function pair(array $transactionData): ?Transaction
    {
        if (! empty($transactionData['is_transfer']) || empty($transactionData['account_id'])) {
            return null;
        }

        $incomingHere = $transactionData['direction'] === Transaction::DIRECTION_DEBIT;
        $date = substr((string) $transactionData['date'], 0, 10);

        $candidates = Transaction::query()
            ->where('team_id', $transactionData['team_id'])
            ->where('account_id', '!=', $transactionData['account_id'])
            ->whereIn('account_id', Account::query()->select('id')->where('team_id', $transactionData['team_id']))
            ->where('direction', $incomingHere ? Transaction::DIRECTION_CREDIT : Transaction::DIRECTION_DEBIT)
            ->whereRaw('ABS(total - ?) < 0.005', [(float) $transactionData['total']])
            ->where('currency_code', $transactionData['currency_code'])
            ->whereDate('date', '>=', date('Y-m-d', strtotime($date.' -'.self::DAY_WINDOW.' days')))
            ->whereDate('date', '<=', date('Y-m-d', strtotime($date.' +'.self::DAY_WINDOW.' days')))
            ->where(fn ($query) => $query->whereNull('is_transfer')->orWhere('is_transfer', false))
            ->where(fn ($query) => $query->whereNull('has_splits')->orWhere('has_splits', false))
            ->where(fn ($query) => $query
                ->where('status', '!=', Transaction::STATUS_VERIFIED)
                ->orWhereIn('id', ProcessedEmail::query()->select('transaction_id')->whereNotNull('transaction_id')))
            ->limit(2)
            ->get();

        if ($candidates->count() !== 1) {
            return null;
        }

        return self::convert($candidates->first(), $transactionData, $incomingHere);
    }

    /**
     * @param  array<string, mixed>  $transactionData
     */
    private static function convert(Transaction $existing, array $transactionData, bool $incomingHere): Transaction
    {
        $sourceAccountId = $incomingHere ? $existing->account_id : $transactionData['account_id'];
        $targetAccountId = $incomingHere ? $transactionData['account_id'] : $existing->account_id;
        $outgoingDate = $incomingHere ? substr((string) $existing->date, 0, 10) : substr((string) $transactionData['date'], 0, 10);

        return $existing->updateTransaction([
            ...$existing->only(['team_id', 'user_id', 'status', 'total', 'currency_code', 'reference', 'meta_data']),
            'account_id' => $sourceAccountId,
            'counter_account_id' => $targetAccountId,
            'direction' => Transaction::DIRECTION_CREDIT,
            'date' => $outgoingDate,
            'description' => 'Transferencia a '.Account::find($targetAccountId)?->name,
            'is_transfer' => true,
            'payee_id' => null,
            'category_id' => null,
        ]);
    }
}

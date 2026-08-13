<?php

namespace App\Http\Controllers;

use App\Domains\Transaction\Models\Transaction;
use Illuminate\Http\Request;
use Insane\Journal\Models\Core\Payee;

/**
 * Cross-resource search behind the header palette. Unlike the per-page
 * AppSearch filters, this one is not date-scoped — a global search that only
 * looked at the current month would be useless for "where did I buy that".
 */
class SearchController extends Controller
{
    /**
     * Shorter terms match almost everything, so the palette stays idle until
     * the user has committed to something specific.
     */
    private const MIN_LENGTH = 3;

    private const LIMIT_PER_GROUP = 8;

    /**
     * Empty groups are dropped so the palette can build its tab list straight
     * from the response keys.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function index(Request $request): array
    {
        $searchText = trim((string) $request->query('search', ''));

        if (mb_strlen($searchText) < self::MIN_LENGTH) {
            return [];
        }

        $teamId = $request->user()->current_team_id;

        return array_filter([
            'transactions' => $this->searchTransactions($teamId, $searchText),
            'payees' => $this->searchPayees($teamId, $searchText),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchTransactions(int $teamId, string $searchText): array
    {
        $term = '%'.$this->escapeLike($searchText).'%';

        // If the query looks like a number, also match by amount (either sign) —
        // typing "3000" should surface a 3,000 transaction, not only rows whose
        // description happens to contain those digits.
        $digits = str_replace([',', ' '], '', $searchText);
        $amount = is_numeric($digits) ? (float) $digits : null;

        return Transaction::query()
            ->where('transactions.team_id', $teamId)
            ->where('transactions.status', Transaction::STATUS_VERIFIED)
            ->where(function ($query) use ($term, $amount) {
                $query->where('transactions.description', 'like', $term)
                    ->orWhereHas('payee', function ($payee) use ($term) {
                        $payee->where('name', 'like', $term);
                    });

                if ($amount !== null) {
                    $query->orWhereRaw('ABS(transactions.total) = ?', [$amount]);
                }
            })
            ->with(['payee:id,name', 'category:id,name'])
            ->orderByDesc('transactions.date')
            ->orderByDesc('transactions.id')
            ->limit(self::LIMIT_PER_GROUP)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'type' => 'transactions',
                'id' => $transaction->id,
                'title' => $transaction->description,
                'subtitle' => $transaction->payee?->name,
                'meta' => $transaction->category?->name,
                'date' => $transaction->date,
                'total' => $transaction->total,
                'direction' => $transaction->direction,
                'currency_code' => $transaction->currency_code,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchPayees(int $teamId, string $searchText): array
    {
        $term = '%'.$this->escapeLike($searchText).'%';

        return Payee::query()
            ->where('team_id', $teamId)
            ->where('name', 'like', $term)
            ->orderBy('name')
            ->limit(self::LIMIT_PER_GROUP)
            ->get()
            ->map(fn (Payee $payee) => [
                'type' => 'payees',
                'id' => $payee->id,
                'title' => $payee->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Wildcards typed by the user are data, not operators — a bare `%` would
     * otherwise match the whole table.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}

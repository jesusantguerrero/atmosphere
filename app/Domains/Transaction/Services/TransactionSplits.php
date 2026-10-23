<?php

namespace App\Domains\Transaction\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Insane\Journal\Models\Core\Transaction;

/**
 * A purchase split across several categories (a supermarket receipt: food, cleaning, pharmacy). The web sends
 * `has_splits` + `items`; the journal package turns each item into a pair of lines (the category side and the
 * account side). These are the rules and the shape the mobile API uses for that.
 */
class TransactionSplits
{
    /** Validation rules for `items`: at least two lines, each with its category and amount. */
    public static function rules(int $teamId): array
    {
        $teamRow = fn (string $table) => Rule::exists($table, 'id')->whereIn('team_id', [$teamId, 0]);

        return [
            'items' => ['sometimes', 'array', 'min:2', 'max:30'],
            'items.*.category_id' => ['required', $teamRow('categories')],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
            'items.*.payee_id' => ['nullable', $teamRow('payees')],
            'items.*.concept' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The items as `Transaction::createLines()` wants them. The amounts must add up to the total (to the cent),
     * and every line needs a payee: the line's own, else the transaction's, else the journal's default one.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function lines(array $items, float $total, mixed $transactionPayeeId): array
    {
        $sum = round(array_sum(array_map(fn ($item) => (float) $item['amount'], $items)), 2);
        if (abs($sum - round($total, 2)) > 0.01) {
            throw ValidationException::withMessages(['items' => __('The split amounts must add up to the total.')]);
        }

        return array_map(fn ($item) => [
            'category_id' => (int) $item['category_id'],
            'amount' => (float) $item['amount'],
            'payee_id' => $item['payee_id'] ?? $transactionPayeeId ?? 'new',
            'concept' => $item['concept'] ?? null,
        ], array_values($items));
    }

    /**
     * The split lines of a transaction, for the app to show and edit.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function of(Transaction $transaction): Collection
    {
        if (! $transaction->has_splits) {
            return collect();
        }

        return $transaction->splits()
            ->withoutEagerLoads()
            ->leftJoin('categories', 'categories.id', 'transaction_lines.category_id')
            ->orderBy('transaction_lines.id')
            ->get(['transaction_lines.id', 'transaction_lines.category_id', 'categories.name as category_name', 'transaction_lines.amount', 'transaction_lines.payee_id', 'transaction_lines.concept'])
            ->map(fn ($line) => [
                'id' => $line->id,
                'category_id' => $line->category_id ? (int) $line->category_id : null,
                'category_name' => $line->category_name,
                'amount' => (float) $line->amount,
                'payee_id' => $line->payee_id ? (int) $line->payee_id : null,
                'concept' => $line->concept,
            ])
            ->values();
    }
}

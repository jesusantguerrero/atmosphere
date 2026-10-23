<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionLine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SearchTransactions
{
    public const DATE_AFTER = 1;

    public const DATE_BEFORE = 1;

    private const TEXT_OPERATORS = ['contains' => 'LIKE', 'is' => '='];

    private const ID_FIELDS = ['category_id', 'payee_id', 'account_id'];

    private function normalizeFields(array $conditions): array
    {
        $valid = [];
        foreach (self::ID_FIELDS as $field) {
            $ids = array_values(array_filter((array) ($conditions[$field] ?? []), fn ($id) => is_scalar($id) && ctype_digit((string) $id) && (int) $id > 0));
            if ($ids) {
                $valid[$field] = $ids;
            }
        }
        foreach ((array) ($conditions['description'] ?? []) as $condition) {
            if (is_array($condition) && is_string($condition['operator'] ?? null) && isset(self::TEXT_OPERATORS[$condition['operator']]) && is_scalar($condition['value'] ?? null) && trim((string) $condition['value']) !== '') {
                $valid['description'][] = $condition;
            }
        }

        return $valid;
    }

    private function validConditions(mixed $conditions): array
    {
        if (! is_array($conditions)) {
            return [];
        }

        $valid = $this->normalizeFields($conditions);
        foreach ((array) ($conditions['groups'] ?? []) as $group) {
            if (is_array($group) && ($fields = $this->normalizeFields($group))) {
                $valid['groups'][] = ['match' => ($group['match'] ?? 'all') === 'any' ? 'any' : 'all', ...$fields];
            }
        }
        if (! $valid) {
            return [];
        }

        $valid['match'] = ($conditions['match'] ?? 'all') === 'any' ? 'any' : 'all';
        $valid['exclude'] = is_array($conditions['exclude'] ?? null) ? $this->normalizeFields($conditions['exclude']) : [];

        return $valid;
    }

    public function hasValidConditions(mixed $conditions): bool
    {
        return $this->validConditions($conditions) !== [];
    }

    private function applyNode(Builder $query, array $conditions): void
    {
        $method = ($conditions['match'] ?? 'all') === 'any' ? 'orWhere' : 'where';
        foreach (self::ID_FIELDS as $field) {
            if (! isset($conditions[$field])) {
                continue;
            }
            $query->{$method}(function (Builder $query) use ($field, $conditions): void {
                if ($field === 'category_id') {
                    $query->whereIn($field, $conditions[$field]);
                } else {
                    $query->whereHas('transaction', fn (Builder $query) => $query->whereIn($field, $conditions[$field]));
                }
            });
        }
        foreach ($conditions['description'] ?? [] as $condition) {
            $operator = self::TEXT_OPERATORS[$condition['operator']];
            $value = $operator === 'LIKE' ? "%{$condition['value']}%" : $condition['value'];
            $query->{$method}('concept', $operator, $value);
        }
        foreach ($conditions['groups'] ?? [] as $group) {
            $query->{$method}(fn (Builder $query) => $this->applyNode($query, $group));
        }
    }

    /**
     * Verified lines of one team matching the occurrence conditions.
     */
    public function handle(mixed $conditions, int $teamId): ?Collection
    {
        $conditions = $this->validConditions($conditions);
        if (! $conditions) {
            return null;
        }

        $query = TransactionLine::query()->where('team_id', $teamId)
            ->where(fn (Builder $query) => $this->applyNode($query, $conditions));
        if ($conditions['exclude']) {
            $query->whereNot(fn (Builder $query) => $this->applyNode($query, ['match' => 'any', ...$conditions['exclude']]));
        }

        return $query->whereHas('transaction', fn (Builder $query) => $query->where('status', Transaction::STATUS_VERIFIED))
            ->where(fn (Builder $query) => $query->where('anchor', 1)->orWhere('is_split', 1))
            ->orderBy('date')->orderBy('id')->get();
    }
}

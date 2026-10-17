<?php

namespace App\Domains\Budget\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\CategoryMerge;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CategoryMergeService
{
    private const AMOUNTS = ['budgeted', 'activity', 'available', 'funded_spending', 'payments', 'left_from_last_month', 'moved_from_last_month', 'overspending_previous_month', 'accounts_balance'];

    public function protected(Category $category): bool
    {
        return $category->resource_type !== 'transactions'
            || ! $category->parent_id || $category->account_id || $category->resource_type_id
            || in_array($category->display_id, ['ready_to_assign', 'inflow', 'credit_card_payments'])
            || $category->accounts()->exists()
            || $category->getConnection()->table('budget_default_categories')->where('category_id', $category->id)->exists();
    }

    /** @return array<string, array<int, string>> */
    private function references(): array
    {
        $references = [];
        foreach (Schema::getTables() as $table) {
            $name = $table['name'];
            if ($name === 'category_merges') {
                continue;
            }
            $columns = Schema::getColumnListing($name);
            $fields = array_values(array_intersect(['category_id', 'source_category_id', 'destination_category_id'], $columns));
            if ($fields) {
                if (! in_array('id', $columns)) {
                    throw ValidationException::withMessages(['merge' => 'Hay una integración que requiere revisión antes de unificar.']);
                }
                $references[$name] = $fields;
            }
        }
        if (Schema::hasTable('watchlists')) {
            $references['watchlists'] = ['@input'];
        }
        if (Schema::hasTable('automation_task_actions')) {
            $references['automation_task_actions'] = ['@values'];
        }
        ksort($references);

        return $references;
    }

    private function snapshot(Category $source, Category $target, array $references): array
    {
        $connection = $source->getConnection();
        $ids = [$source->id, $target->id];
        $snapshot = ['categories' => $connection->table('categories')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->map(fn ($row) => (array) $row)->all()];
        foreach ($references as $table => $columns) {
            if (str_starts_with($columns[0], '@')) {
                $query = $connection->table($table)->where('team_id', $source->team_id);
                if ($table === 'watchlists') {
                    $query->where('type', 'categories');
                }
                $field = substr($columns[0], 1);
                $snapshot[$table] = $query->orderBy('id')->lockForUpdate()->get()->filter(function ($row) use ($field, $table, $ids) {
                    $value = json_decode($row->{$field} ?? 'null', true);
                    if (! is_array($value)) {
                        return false;
                    }
                    if ($table === 'watchlists') {
                        return count(array_intersect($ids, $value)) > 0;
                    }

                    return $this->containsCategoryReference($value, $ids);
                })->map(fn ($row) => (array) $row)->values()->all();

                continue;
            }
            $snapshot[$table] = $connection->table($table)->where(function ($query) use ($columns, $ids) {
                foreach ($columns as $column) {
                    $query->orWhereIn($column, $ids);
                }
            })->orderBy('id')->lockForUpdate()->get()->map(fn ($row) => (array) $row)->all();
        }

        return $snapshot;
    }

    private function hash(array $snapshot): string
    {
        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
    }

    private function blockers(Category $source, Category $target, array $snapshot, ?string $goal = null): array
    {
        $errors = [];
        if ($source->id === $target->id || $this->protected($source) || $this->protected($target)) {
            $errors[] = 'Elige dos categorías de gasto normales. Las categorías automáticas y predeterminadas están protegidas.';
        }
        foreach (['budget_targets', 'budget_match_accounts'] as $table) {
            $rows = collect($snapshot[$table] ?? []);
            if ($rows->where('category_id', $source->id)->isNotEmpty() && $rows->where('category_id', $target->id)->isNotEmpty() && ($table !== 'budget_targets' || ! in_array($goal, ['source', 'target']))) {
                $errors[] = $table === 'budget_targets'
                    ? 'Ambas categorías tienen metas. Revisa y conserva una sola meta antes de unificarlas; no se sobrescribirá ninguna.'
                    : 'Ambas categorías tienen cuentas vinculadas. Revisa esos vínculos antes de unificarlas.';
            }
        }
        if (collect($snapshot['budget_targets'] ?? [])->groupBy('category_id')->contains(fn ($items) => $items->count() > 1)) {
            $errors[] = 'Una categoría tiene varias metas. Requiere revisión antes de unificar.';
        }
        foreach (collect($snapshot['budget_months'] ?? [])->groupBy('month') as $rows) {
            if ($rows->pluck('currency_code')->filter()->unique()->count() > 1 || $rows->groupBy('category_id')->contains(fn ($items) => $items->count() > 1)) {
                $errors[] = 'Hay períodos con monedas diferentes o registros duplicados. Revísalos antes de unificar.';
            }
            if ($rows->count() > 1 && $rows->pluck('meta_data')->filter()->unique()->count() > 1) {
                $errors[] = 'Hay detalles de presupuesto incompatibles en un mismo mes. Requieren revisión.';
            }
        }

        return array_values(array_unique($errors));
    }

    public function preview(Category $source, Category $target): array
    {
        $snapshot = $this->snapshot($source, $target, $this->references());

        return [
            'token' => $this->hash($snapshot), 'source' => $source->name, 'target' => $target->name,
            'transactions' => collect($snapshot['transactions'] ?? [])->where('category_id', $source->id)->count(),
            'lines' => collect($snapshot['transaction_lines'] ?? [])->where('category_id', $source->id)->count(),
            'months' => collect($snapshot['budget_months'] ?? [])->where('category_id', $source->id)->count(),
            'periods' => collect($snapshot['budget_months'] ?? [])->groupBy('month')->map(fn ($rows, $month) => [
                'month' => $month, 'currency' => $rows->pluck('currency_code')->filter()->first(),
                'budgeted' => $rows->reduce(fn ($sum, $row) => (string) BigDecimal::of($sum)->plus($row['budgeted']), '0'),
            ])->values(),
            'blockers' => $this->blockers($source, $target, $snapshot),
            'goals' => collect($snapshot['budget_targets'] ?? [])->map(fn ($row) => [
                'side' => (int) $row['category_id'] === $source->id ? 'source' : 'target',
                'name' => $row['name'], 'amount' => $row['amount'], 'type' => $row['target_type'],
            ])->values(),
        ];
    }

    public function merge(Category $source, Category $target, int $userId, string $token, ?string $goal = null): CategoryMerge
    {
        return $source->getConnection()->transaction(function () use ($source, $target, $userId, $token, $goal) {
            Category::query()->whereIn('id', [$source->id, $target->id])->orderBy('id')->lockForUpdate()->get();
            $source->refresh();
            $target->refresh();
            $references = $this->references();
            $before = $this->snapshot($source, $target, $references);
            $errors = $this->blockers($source, $target, $before, $goal);
            if ($errors) {
                throw ValidationException::withMessages(['merge' => $errors]);
            }
            if (! hash_equals($this->hash($before), $token)) {
                throw ValidationException::withMessages(['merge' => 'Los datos cambiaron. Genera una nueva vista previa.']);
            }
            $connection = $source->getConnection();
            $goals = collect($before['budget_targets'] ?? []);
            if ($goals->count() === 2) {
                $keepCategory = $goal === 'source' ? $source->id : $target->id;
                $discarded = $goals->first(fn ($row) => (int) $row['category_id'] !== $keepCategory);
                $connection->table('budget_targets')->where('id', $discarded['id'])->delete();
            }
            foreach (collect($before['budget_months'] ?? [])->where('category_id', $source->id) as $month) {
                $destination = collect($before['budget_months'])->first(fn ($row) => (int) $row['category_id'] === $target->id && $row['month'] === $month['month']);
                if ($destination) {
                    $values = [];
                    foreach (self::AMOUNTS as $field) {
                        if (array_key_exists($field, $month)) {
                            $values[$field] = (string) BigDecimal::of($month[$field] ?? 0)->plus($destination[$field] ?? 0);
                        }
                    }
                    if (empty($destination['meta_data']) && ! empty($month['meta_data'])) {
                        $values['meta_data'] = $month['meta_data'];
                    }
                    $values['name'] = $target->name;
                    $connection->table('budget_months')->where('id', $destination['id'])->update($values);
                    $connection->table('budget_months')->where('id', $month['id'])->delete();
                }
            }
            foreach ($references as $table => $columns) {
                if (str_starts_with($columns[0], '@')) {
                    $field = substr($columns[0], 1);
                    foreach ($before[$table] as $row) {
                        $value = json_decode($row[$field] ?? 'null', true);
                        if (! is_array($value)) {
                            continue;
                        }
                        if ($table === 'watchlists') {
                            $value = array_values(array_unique(array_map(fn ($id) => (string) $id === (string) $source->id ? $target->id : $id, $value)));
                        } else {
                            $value = $this->replaceCategoryReference($value, $source->id, $target->id);
                        }
                        if ($value !== json_decode($row[$field] ?? 'null', true)) {
                            $connection->table($table)->where('id', $row['id'])->update([$field => json_encode($value, JSON_THROW_ON_ERROR)]);
                        }
                    }

                    continue;
                }
                foreach ($columns as $column) {
                    $connection->table($table)->where($column, $source->id)->update([$column => $target->id]);
                }
            }
            $connection->table('budget_months')->where('category_id', $target->id)->update(['name' => $target->name]);
            foreach (['source', 'destination'] as $side) {
                $connection->table('budget_movements')->where($side.'_category_id', $target->id)->update([$side.'_category_name' => $target->name]);
            }
            $sourceName = $source->name;
            $source->update([
                'resource_type' => 'merged_category', 'parent_id' => null,
                'name' => 'Unificada #'.$source->id,
                'display_id' => 'merged_category_'.$source->id,
            ]);

            return CategoryMerge::create([
                'team_id' => $source->team_id, 'user_id' => $userId,
                'source_id' => $source->id, 'target_id' => $target->id,
                'source_name' => $sourceName, 'target_name' => $target->name,
                'snapshot' => $before, 'references' => $references,
                'after_hash' => $this->hash($this->snapshot($source, $target, $references)),
            ]);
        });
    }

    private function containsCategoryReference(array $value, array $ids): bool
    {
        foreach ($value as $key => $item) {
            if (is_array($item) && $this->containsCategoryReference($item, $ids)) {
                return true;
            }
            if ($key === 'category_id' && ! is_array($item) && in_array($item, $ids)) {
                return true;
            }
        }

        return false;
    }

    private function replaceCategoryReference(array $value, int $sourceId, int $targetId): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->replaceCategoryReference($item, $sourceId, $targetId);
            } elseif ($key === 'category_id' && (string) $item === (string) $sourceId) {
                $value[$key] = $targetId;
            }
        }

        return $value;
    }

    public function undo(CategoryMerge $merge): void
    {
        $merge->getConnection()->transaction(function () use ($merge) {
            $merge = CategoryMerge::query()->lockForUpdate()->findOrFail($merge->id);
            $source = Category::findOrFail($merge->source_id);
            $target = Category::findOrFail($merge->target_id);
            $current = $this->snapshot($source, $target, $merge->references);
            if ($merge->undone_at || ! hash_equals($merge->after_hash, $this->hash($current))) {
                throw ValidationException::withMessages(['merge' => 'No se puede deshacer automáticamente porque hubo cambios posteriores. No se ha modificado nada.']);
            }
            foreach ($merge->snapshot as $table => $rows) {
                foreach ($rows as $row) {
                    $merge->getConnection()->table($table)->updateOrInsert(['id' => $row['id']], $row);
                }
            }
            $merge->update(['undone_at' => now()]);
        });
    }
}

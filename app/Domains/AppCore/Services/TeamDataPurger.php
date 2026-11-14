<?php

namespace App\Domains\AppCore\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hard-deletes every row a team owns, across the app and its modules.
 *
 * Team tables are discovered by their `team_id` column, so tables added by
 * new modules are covered without touching this class.
 */
class TeamDataPurger
{
    /**
     * Pivot tables without a `team_id`, keyed by table, with the column that
     * points at a team-owned parent table.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const CHILD_TABLES = [
        'account_budgets' => ['account_id', 'accounts'],
        'budget_categories' => ['budget_id', 'budgets'],
        'labelables' => ['label_id', 'labels'],
    ];

    public function purge(int $teamId): void
    {
        DB::transaction(function () use ($teamId) {
            Schema::withoutForeignKeyConstraints(function () use ($teamId) {
                foreach (self::CHILD_TABLES as $table => [$foreignKey, $parentTable]) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }

                    DB::table($table)
                        ->whereIn($foreignKey, DB::table($parentTable)->select('id')->where('team_id', $teamId))
                        ->delete();
                }

                foreach ($this->teamTables() as $table) {
                    DB::table($table)->where('team_id', $teamId)->delete();
                }
            });
        });
    }

    /**
     * @return list<string>
     */
    public function teamTables(): array
    {
        return collect(Schema::getTables())
            ->pluck('name')
            ->reject(fn (string $table) => $table === 'teams')
            ->filter(fn (string $table) => Schema::hasColumn($table, 'team_id'))
            ->values()
            ->all();
    }
}

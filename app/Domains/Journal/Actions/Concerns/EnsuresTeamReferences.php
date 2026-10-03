<?php

namespace App\Domains\Journal\Actions\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rejects transaction payloads that point at accounts, payees or categories
 * of another team. Global rows (team_id = 0) stay allowed.
 */
trait EnsuresTeamReferences
{
    /**
     * @var array<string, string>
     */
    private array $teamReferenceTables = [
        'account_id' => 'accounts',
        'counter_account_id' => 'accounts',
        'payee_id' => 'payees',
        'category_id' => 'categories',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    protected function ensureTeamReferences(int $teamId, array $data): void
    {
        $rows = [$data, ...array_values(array_filter($data['items'] ?? [], 'is_array'))];

        foreach ($rows as $row) {
            foreach ($this->teamReferenceTables as $field => $table) {
                $id = $row[$field] ?? null;
                if (! is_numeric($id) || (int) $id <= 0) {
                    continue;
                }

                $belongsToTeam = DB::table($table)
                    ->where('id', (int) $id)
                    ->whereIn('team_id', [$teamId, 0])
                    ->exists();

                if (! $belongsToTeam) {
                    throw ValidationException::withMessages([$field => 'The selected value does not belong to this team.']);
                }
            }
        }
    }
}

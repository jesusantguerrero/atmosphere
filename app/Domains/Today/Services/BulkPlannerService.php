<?php

namespace App\Domains\Today\Services;

use App\Domains\AppCore\Models\Planner;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
use Illuminate\Support\Collection;

/**
 * Bulk creator of free-form Planner rows. Used by the Today bulk-add modal so
 * the user can dump a monthly batch (e.g. school activities) in one shot
 * instead of N round-trips.
 */
class BulkPlannerService
{
    public function __construct(private ProfileWorkspaceService $workspace) {}

    /**
     * @param  array<int, array{date: string, end_date?: ?string, name: string, notes?: ?string, total?: ?float}>  $items
     * @return Collection<int, Planner>
     */
    public function createMany(int $teamId, int $userId, array $items, ?string $source = null, ?int $profileId = null): Collection
    {
        return collect($items)->map(fn (array $item) => tap(Planner::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'name' => $item['name'],
            'notes' => $item['notes'] ?? null,
            'total' => $item['total'] ?? null,
            'source' => $source,
            'date' => $item['date'],
            'end_date' => $item['end_date'] ?? $item['date'],
            'frequency' => 'DAILY',
            'status' => Planner::STATUS_PENDING,
        ]), fn (Planner $planner) => $this->workspace->setEventProfile($planner, $profileId)));
    }
}

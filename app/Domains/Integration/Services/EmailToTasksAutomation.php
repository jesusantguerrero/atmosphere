<?php

namespace App\Domains\Integration\Services;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Actions\CreateTaskFromEmail;
use App\Domains\Integration\Actions\GmailReceived;
use App\Domains\Integration\Models\Integration;
use App\Models\User;

/**
 * Single source of truth for the "emails -> tasks" automation, shared by the
 * setup command and the one-click UI toggle. Enabling it builds the
 * GmailReceived -> CreateTaskFromEmail chain (same engine as the bank sync);
 * disabling just flips its status off so the config/tasks are kept for re-enable.
 */
class EmailToTasksAutomation
{
    public const NAME = 'Email to Tasks';

    public const DEFAULT_QUERY = 'is:starred';

    public const DEFAULT_BOARD = 'Email';

    public static function find(User $user, ?int $teamId = null): ?Automation
    {
        return Automation::where([
            'user_id' => $user->id,
            'team_id' => $teamId ?? $user->current_team_id,
            'name' => self::NAME,
        ])->first();
    }

    /**
     * Current state for the UI: whether it's on, whether Google is connected
     * (needed to enable it), and the active Gmail query.
     *
     * @return array{enabled: bool, exists: bool, connected: bool, query: string, board: string}
     */
    public static function status(User $user, ?int $teamId = null): array
    {
        $automation = self::find($user, $teamId);

        return [
            'enabled' => (bool) ($automation?->status),
            'exists' => (bool) $automation,
            'connected' => (bool) self::googleIntegration($user, $teamId),
            'query' => $automation ? self::queryOf($automation) : self::DEFAULT_QUERY,
            'board' => self::DEFAULT_BOARD,
        ];
    }

    public static function enable(User $user, ?string $query = null, ?string $board = null, ?int $teamId = null): Automation
    {
        $teamId = $teamId ?? $user->current_team_id;
        $integration = self::googleIntegration($user, $teamId);
        $query = $query ?: self::DEFAULT_QUERY;
        $board = $board ?: self::DEFAULT_BOARD;

        $automation = Automation::updateOrCreate(
            [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'name' => self::NAME,
            ],
            [
                'integration_id' => $integration?->id,
                'trigger_id' => 1,
                'description' => 'Turns selected emails (default: starred) into task cards on a board.',
                'sentence' => 'When a matching email is received, create a task',
                'status' => true,
                'is_background' => true,
                'config' => [],
            ]
        );

        $automation->saveTasks([
            [
                'entity' => GmailReceived::class,
                'task_type' => 'trigger',
                'order' => 0,
                'name' => 'Gmail Trigger - Tasks',
                'values' => ['query' => $query],
            ],
            [
                'entity' => CreateTaskFromEmail::class,
                'task_type' => 'action',
                'order' => 1,
                'name' => 'Create Task From Email',
                'values' => ['board' => $board],
            ],
        ]);

        return $automation;
    }

    public static function disable(User $user, ?int $teamId = null): void
    {
        self::find($user, $teamId)?->update(['status' => false]);
    }

    /** The token-bearing Google/Gmail integration this user connected. */
    private static function googleIntegration(User $user, ?int $teamId): ?Integration
    {
        return Integration::where('user_id', $user->id)
            ->where('team_id', $teamId ?? $user->current_team_id)
            ->whereNotNull('token')
            ->where(function ($q) {
                $q->whereIn('name', ['Google', 'Gmail'])
                    ->orWhereHas('service', fn ($s) => $s->whereIn('name', ['Google', 'Gmail']));
            })
            ->first();
    }

    private static function queryOf(Automation $automation): string
    {
        $trigger = $automation->tasks->firstWhere('order', 0);
        $values = $trigger ? json_decode($trigger->values, true) : null;

        return is_array($values) ? ($values['query'] ?? self::DEFAULT_QUERY) : self::DEFAULT_QUERY;
    }
}

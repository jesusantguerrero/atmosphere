<?php

namespace App\Mcp\Concerns;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

/**
 * Team context shared by the Loger MCP tools: every tool works on the token
 * owner's current household, in its time zone.
 */
trait InteractsWithTeam
{
    public const WRITE_ABILITY = 'mcp:write';

    protected function teamId(Request $request): int
    {
        return (int) $request->user()->current_team_id;
    }

    protected function today(Request $request): Carbon
    {
        $timezone = Setting::getByTeam($this->teamId($request))['team_timezone'] ?? 'America/Santo_Domingo';

        return Carbon::now($timezone)->startOfDay();
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function monthRange(Request $request, ?string $month): array
    {
        $start = $month ? Carbon::createFromFormat('Y-m-d', "{$month}-01")->startOfDay() : $this->today($request)->startOfMonth();

        return [$start->format('Y-m-d'), $start->copy()->endOfMonth()->format('Y-m-d')];
    }

    /**
     * Read tools only need `mcp:read`; tools that change data also need the `mcp:write` scope.
     */
    protected function denyWithoutWriteAbility(Request $request): ?Response
    {
        /** @var User $user */
        $user = $request->user();

        return $user->tokenCan(self::WRITE_ABILITY) ? null : Response::error('This connection is read-only. Reconnect Loger from Claude Code and allow recording transactions.');
    }
}

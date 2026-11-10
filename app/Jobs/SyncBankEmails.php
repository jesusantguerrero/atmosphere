<?php

namespace App\Jobs;

use App\Domains\Integration\Actions\GmailReceived;
use App\Domains\Integration\Services\UniversalBankAutomationService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * The «Sync now» button: reads every bank email since the last import (with a
 * few days of margin, to fill gaps) and then lets the minute check continue
 * forward. The outcome is kept for the status card.
 */
class SyncBankEmails
{
    use Dispatchable;
    use Queueable;

    public function __construct(public int $userId, public int $teamId) {}

    public function handle(): void
    {
        set_time_limit(300);
        $user = User::query()->findOrFail($this->userId);
        $automation = UniversalBankAutomationService::find($user, $this->teamId);
        $since = UniversalBankAutomationService::syncSince($this->teamId, $automation?->id);

        try {
            if (! $automation) {
                throw new \RuntimeException('Bank email import is not set up.');
            }

            $result = GmailReceived::sync($automation, $since);
            UniversalBankAutomationService::rememberSync($this->teamId, [
                'status' => 'done',
                'since' => $since,
                ...$result,
            ]);
        } catch (\Throwable $exception) {
            Log::error('SyncBankEmails failed', ['team_id' => $this->teamId, 'error' => $exception->getMessage()]);
            UniversalBankAutomationService::rememberSync($this->teamId, [
                'status' => 'failed',
                'since' => $since,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public static function isRunning(int $teamId): bool
    {
        return (Cache::get(UniversalBankAutomationService::syncCacheKey($teamId))['status'] ?? null) === 'running';
    }
}

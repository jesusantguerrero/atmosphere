<?php

namespace App\Jobs;

use App\Domains\Budget\Services\BudgetRolloverService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Recomputes budget availability for a team from a given month forward.
 *
 * Every verified transaction event used to run the full rollover inline, so a
 * bank import of N transactions ran N rollovers. This job is unique per
 * team + month while it waits in the queue: dispatches that land during that
 * window collapse into the pending job. The lock is released when processing
 * starts, so a transaction saved mid-rollover still queues a trailing run that
 * picks it up.
 */
class RollBudgetForward implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Seconds before a stale lock (dead worker) expires and a new job may be queued.
     */
    public int $uniqueFor = 120;

    /**
     * @param  string  $yearMonth  first month to recompute, `YYYY-MM`
     */
    public function __construct(private int $teamId, private string $yearMonth) {}

    public function uniqueId(): string
    {
        return "{$this->teamId}:{$this->yearMonth}";
    }

    public function handle(BudgetRolloverService $rolloverService): void
    {
        $rolloverService->startFrom($this->teamId, $this->yearMonth);
    }
}

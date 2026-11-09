<?php

namespace App\Jobs;

use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Domains\Housing\Models\Occurrence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Re-syncs a team's occurrences. Unique per team for a minute, so approving a
 * batch of drafts (each fires TransactionUpdated) queues one sync, not one per draft.
 */
class RunTeamChecks implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(private string $teamId)
    {
        //
    }

    public function uniqueId(): string
    {
        return $this->teamId;
    }

    /**
     * Execute the job.
     */
    public function handle(RegisterOccurrence $registerer): void
    {
        $checks = Occurrence::where([
            'team_id' => $this->teamId,
            'is_active' => true,
        ])->get();

        foreach ($checks as $check) {
            $registerer->sync($check);
        }
    }
}

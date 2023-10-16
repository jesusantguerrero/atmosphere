<?php

namespace App\Jobs;

use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Domains\Housing\Models\Occurrence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunTeamChecks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private string $teamId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(RegisterOccurrence $registerer): void
    {
        $checks = Occurrence::where([
            'team_id' => $this->teamId,
        ])->get();

        foreach ($checks as $check) {
            $registerer->sync($check);
        }
    }
}

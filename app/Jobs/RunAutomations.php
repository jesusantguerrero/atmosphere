<?php

namespace App\Jobs;

use App\Actions\Integrations\Gmail;
use App\Models\Integrations\Automation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunAutomations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $automation;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Automation $automation)
    {
        $this->automation = $automation;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->automation->dispatch();
    }
}

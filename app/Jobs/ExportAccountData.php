<?php

namespace App\Jobs;

use App\Domains\AppCore\Services\AccountDataExporter;
use App\Models\User;
use App\Notifications\AccountDataExportReady;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportAccountData implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public function __construct(public User $user) {}

    public function handle(AccountDataExporter $exporter): void
    {
        $path = $exporter->export($this->user);

        $this->user->notify(new AccountDataExportReady(pathinfo($path, PATHINFO_FILENAME)));
    }
}

<?php

namespace App\Console\Commands;

use App\Domains\AppCore\Services\AccountDataExporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneDataExports extends Command
{
    protected $signature = 'loger:prune-data-exports';

    protected $description = 'Delete account data exports older than the days they are kept for';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $cutoff = now()->subDays(AccountDataExporter::DAYS_KEPT)->getTimestamp();

        foreach ($disk->allFiles(AccountDataExporter::DIRECTORY) as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
            }
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;


class UpdateVersion extends Command
{
    protected $signature = 'update:run {--tag=} {--endpoint=} ';
    protected $description = 'Check if there is any update available in the server';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $version = $this->option('version');
        $endpoint = $this->option('endpoint');

        $this->info("Starting update to version $version...");

        // Download the ZIP file from the GitHub release
        $updatePath = storage_path('app/update-package.zip');
        $this->downloadUpdate($endpoint, $updatePath);

        // Unzip the update package
        $zip = new ZipArchive;
        if ($zip->open($updatePath) === TRUE) {
            $zip->extractTo(base_path());
            $zip->close();
            $this->info('Update files extracted successfully.');

            // Run migrations
            Artisan::call('migrate --force');
            $this->info('Migrations completed.');

            // Clear cache
            Artisan::call('cache:clear');
            Artisan::call('config:cache');
            $this->info('Cache cleared and configurations rebuilt.');

            // Update the version file
            file_put_contents(base_path('version.json'), json_encode(['version' => $version]));
            $this->info("Version updated to $version successfully.");
        } else {
            $this->error('Failed to extract the update package.');
        }
    }

    private function downloadUpdate($url, $path)
    {
        $fileContent = file_get_contents($url, false, stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                        'User-Agent: PHP'
                ]
        ]
        ]));
        file_put_contents($path, $fileContent);
        $this->info('Update package downloaded.');
    }
}

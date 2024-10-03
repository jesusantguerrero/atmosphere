<?php

namespace App\Console\Commands;

use ZipArchive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
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
        $version = $this->option('tag');
        $endpoint = $this->option('endpoint');

        $this->info("Starting update to version $version...");

        // Download the ZIP file from the GitHub release
        $updatePath = storage_path('app/update-package.zip');
        $this->downloadUpdate($endpoint, $updatePath);

        $tempPath = storage_path('app/update-temp');
        $this->extractUpdate($updatePath, $tempPath);

        $this->applyUpdate($tempPath);

        Artisan::call('migrate --force');
        $this->info('Migrations completed.');

        // Clear cache
        Artisan::call('cache:clear');
        Artisan::call('config:cache');
        $this->info('Cache cleared and configurations rebuilt.');

        // Update the version file
        file_put_contents(base_path('version.json'), json_encode(['version' => $version]));
        $this->info("Version updated to $version successfully.");

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

    private function extractUpdate($zipPath, $extractTo)
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo($extractTo);
            $zip->close();
            $this->info('Update files extracted successfully.');
        } else {
            $this->error('Failed to extract the update package.');
        }
    }

    private function applyUpdate($tempPath)
    {
        $this->info('Applying update files...');

        // Find the first directory inside the temp path (usually the repo name folder)
        $directories = File::directories($tempPath);
        if (empty($directories)) {
            $this->error('No directories found in the extracted update.');
            return;
        }

        // Assume the first directory is the main one containing the update files
        $updateDir = $directories[0];

        // Get all the files from the update directory and replace the app's files
        $files = File::allFiles($updateDir);
        foreach ($files as $file) {
            // Compute the relative path of each file inside the update folder
            $relativePath = $file->getRelativePathname();

            // Create the destination path in the Laravel app's root directory
            $destination = base_path($relativePath);

            // Create the directory if it doesn't exist
            File::ensureDirectoryExists(dirname($destination));

            // Copy the file to the correct location in the app
            File::copy($file->getRealPath(), $destination);
        }

        $this->info('Update applied successfully.');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;


class CheckUpdate extends Command
{
    protected $signature = 'app:check-update';
    protected $description = 'Check if there is any update available in the server';

    public function handle()
    {
        $currentVersion = json_decode(file_get_contents(base_path('version.json')), true)['version'];
        $latestRelease = json_decode(file_get_contents('https://api.github.com/repos/jesusantguerrero/atmosphere/releases/latest', false, stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                        'User-Agent: PHP'
                ]
        ]
        ])), true);
        $latestVersion = $latestRelease['tag_name'];

        if (version_compare($latestVersion, $currentVersion, '>')) {
            Artisan::call('update:run', ['--tag' => $latestVersion, '--endpoint' => $latestRelease['zipball_url']]);
        }
    }
}

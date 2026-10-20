<?php

namespace App\Console\Commands;

use App\Domains\LogerProfile\Models\LogerProfile;
use Database\Seeders\ProfileWorkspaceDemoSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('profiles:seed-workspace-demo {sourceProfile}')]
#[Description('Create a separate local profile with persisted sample boards and links')]
class SeedProfileWorkspaceDemo extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('This command is available only in local development.');

            return self::FAILURE;
        }
        $source = LogerProfile::findOrFail($this->argument('sourceProfile'));
        $profile = app(ProfileWorkspaceDemoSeeder::class)->seedFor($source);
        $this->info('Local demo profile: '.$profile->id.' — '.$profile->name);

        return self::SUCCESS;
    }
}

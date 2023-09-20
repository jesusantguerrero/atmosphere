<?php

namespace App\Console\Commands;

use App\Domains\AppCore\Models\Category;
use App\Domains\LogerProfile\Data\ProfileEntityData;
use App\Domains\LogerProfile\Services\LogerProfileService;
use Illuminate\Console\Command;

class TestProfileEntity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-entity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = new LogerProfileService();
        $service->addProfileEntity(
            new ProfileEntityData(
                null,
                2,
                2,
                2,
                Category::class,
                205,
                'Car maintenance',
                null,
                null
            )
        );
    }
}

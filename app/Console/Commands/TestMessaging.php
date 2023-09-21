<?php

namespace App\Console\Commands;

use App\Models\UserDevice;
use App\Services\MessagingService;
use Illuminate\Console\Command;

class TestMessaging extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-messaging';

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
        $service = new MessagingService();
        $userDevice = UserDevice::find(3);
        $service->occurrenceType([
            'title' => 'test',
        ], $userDevice->device_id);
        echo $userDevice->device_id;
    }
}

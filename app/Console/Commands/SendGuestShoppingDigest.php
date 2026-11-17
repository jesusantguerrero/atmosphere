<?php

namespace App\Console\Commands;

use App\Domains\LogerProfile\Services\GuestAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendGuestShoppingDigest extends Command
{
    protected $signature = 'loger:guest-shopping-digest';

    protected $description = 'Tell each guest, in one alert per list, what others added to her shopping lists since the last run';

    public function handle(GuestAlertService $alerts): int
    {
        $alerts->sendShoppingDigests(Carbon::now());

        return self::SUCCESS;
    }
}

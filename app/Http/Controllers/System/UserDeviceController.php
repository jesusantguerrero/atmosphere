<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;


class UserDeviceController extends Controller
{

    public function index() {
        dd("Hello world");
    }

    public function store(User $user) {
        $deviceId = request()->post('deviceToken');


        UserDevice::create([
            "user_id" => $user->id,
            "team_id" => $user->current_team_id,
            "device_id" => $deviceId
        ]);
    }
}

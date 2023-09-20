<?php

namespace App\Http\Controllers\Api;

use DateTimeZone;
use Illuminate\Http\Request;

class TimezonesApiController extends BaseController
{
    public function index(Request $request)
    {
        return [
            'data' => DateTimeZone::listIdentifiers(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CurrencyHelper;
use App\Models\Meal;
use Illuminate\Http\Request;

class CurrencyApiController extends BaseController
{
    public function index(Request $request) {
        return [
            "data" => CurrencyHelper::getAll()
        ];
    }
}
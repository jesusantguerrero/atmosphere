<?php

namespace App\Domains\Automation\Http\Controllers;

use App\Domains\Automation\Models\AutomationService;
use App\Http\Controllers\Controller;

class AutomationServiceController extends Controller
{
    public function index() {
        return AutomationService::all();
    }
}

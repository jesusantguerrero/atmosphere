<?php

namespace App\Domains\Integration\Http\Controllers;

use App\Domains\Integration\Models\Integration;
use App\Http\Controllers\Api\BaseController;

class ApiIntegrationController extends BaseController
{
    public function __construct()
    {
        $this->model = new Integration();
        $this->searchable = ['name'];
        $this->validationRules = [];
    }
}

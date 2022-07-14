<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProcessGmail;
use App\Libraries\GoogleService;
use Insane\Journal\Models\Core\Payee;

class PayeeApiController extends BaseController
{
    public function __construct()
    {
        $this->model = new Payee();
        $this->searchable = ['name'];
        $this->validationRules = [];
    }
}

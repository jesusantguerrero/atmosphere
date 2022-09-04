<?php

namespace App\Http\Controllers\Api;

use App\Domains\AppCore\Models\Label;

class LabelApiController extends BaseController
{
    public function __construct()
    {
        $this->model = new Label();
        $this->searchable = ['name'];
        $this->validationRules = [];
    }
}

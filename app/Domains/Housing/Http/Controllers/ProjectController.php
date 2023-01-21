<?php

namespace App\Domains\Housing\Http\Controllers;

use App\Http\Controllers\Controller;
use Laravel\Jetstream\Jetstream;

class ProjectController extends Controller
{
    public function __invoke()
    {
        return Jetstream::inertia()->render(request(), 'Housing/Index');
    }
}

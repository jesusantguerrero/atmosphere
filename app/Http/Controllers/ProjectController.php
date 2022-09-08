<?php

namespace App\Http\Controllers;

use Laravel\Jetstream\Jetstream;

class ProjectController extends Controller
{
    public function __invoke()
    {
        return Jetstream::inertia()->render(request(), 'Projects/Index');
    }
}

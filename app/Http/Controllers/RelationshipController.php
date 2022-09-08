<?php

namespace App\Http\Controllers;

use Laravel\Jetstream\Jetstream;

class RelationshipController extends Controller
{
    public function __invoke()
    {
        return Jetstream::inertia()->render(request(), 'Relationships/Index');
    }
}

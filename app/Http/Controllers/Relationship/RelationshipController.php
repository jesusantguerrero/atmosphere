<?php

namespace App\Http\Controllers\Relationship;

use App\Http\Controllers\Controller;
use Laravel\Jetstream\Jetstream;

class RelationshipController extends Controller
{
    public function __invoke()
    {
        return Jetstream::inertia()->render(request(), 'Relationships/Index');
    }
}

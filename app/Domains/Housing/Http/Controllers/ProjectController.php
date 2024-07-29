<?php

namespace App\Domains\Housing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Housing\Models\Occurrence;

class ProjectController extends Controller
{
    public function __invoke()
    {
        return inertia('Housing/Index', [
            'checks' => Occurrence::where('team_id', auth()->user()->current_team_id)->limit(6)->get(),
        ]);
    }
}

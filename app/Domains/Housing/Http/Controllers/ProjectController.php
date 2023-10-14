<?php

namespace App\Domains\Housing\Http\Controllers;

use App\Domains\Housing\Models\Occurrence;
use App\Http\Controllers\Controller;

class ProjectController extends Controller
{
    public function __invoke()
    {
        return inertia('Housing/Index', [
            'checks' => Occurrence::where('team_id', auth()->user()->current_team_id)->limit(4)->get(),
        ]);
    }
}

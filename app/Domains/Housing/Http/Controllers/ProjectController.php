<?php

namespace App\Domains\Housing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Plan\Services\PlanService;
use App\Domains\Housing\Models\Occurrence;

class ProjectController extends Controller
{
    public function __invoke(PlanService $service)
    {
        return inertia('Housing/Index', [
            'checks' => Occurrence::where('team_id', auth()->user()->current_team_id)->limit(6)->get(),
            'boards' => $service->listCustomBoards(auth()->user()->current_team_id)
        ]);
    }
}

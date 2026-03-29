<?php

namespace App\Domains\Housing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;

class ChoreController extends Controller
{
    public function __invoke(PlanService $service)
    {
        $user = request()->user();

        return inertia('Housing/Chores', [
            'chores' => [$service->getPlanType($user->current_team_id, PlanTypes::CHORES, request())],
        ]);
    }
}

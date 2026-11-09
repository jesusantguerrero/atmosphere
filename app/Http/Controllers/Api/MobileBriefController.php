<?php

namespace App\Http\Controllers\Api;

use App\Domains\Today\Services\MeBriefService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\MeBriefRequest;
use Illuminate\Http\JsonResponse;

class MobileBriefController extends Controller
{
    public function __invoke(MeBriefRequest $request, MeBriefService $brief): JsonResponse
    {
        return response()->json($brief->build((int) $request->user()->current_team_id, $request->validated()));
    }
}

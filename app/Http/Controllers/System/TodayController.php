<?php

namespace App\Http\Controllers\System;

use App\Domains\Today\Services\TodayService;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * TODAY-1 v0.1 keystone — daily-first command center.
 * Coexists with /dashboard (deep view); Today is the glance view.
 */
class TodayController
{
    public function __construct(private TodayService $todayService) {}

    public function __invoke(Request $request)
    {
        $user = $request->user();

        $landingPage = Setting::where([
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'name' => 'landing_page',
        ])->value('value') ?? 'dashboard';

        return inertia('Today/Index', [
            'sectionTitle' => 'Today',
            'today' => $this->todayService->buildPayload($user->current_team_id, $user->id),
            'landingPage' => $landingPage,
        ]);
    }
}

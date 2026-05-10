<?php

namespace App\Http\Controllers\System;

use App\Domains\Today\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendarController
{
    public function __construct(private CalendarService $calendarService) {}

    public function __invoke(Request $request)
    {
        $user = $request->user();
        $teamId = $user->current_team_id;

        // Default to current month range
        $start = $request->query('start', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end = $request->query('end', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $events = $this->calendarService->getEvents($teamId, $start, $end);

        return inertia('Calendar/Index', [
            'sectionTitle' => 'Calendar',
            'events' => $events,
            'start' => $start,
            'end' => $end,
        ]);
    }
}

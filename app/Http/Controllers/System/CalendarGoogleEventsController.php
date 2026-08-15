<?php

namespace App\Http\Controllers\System;

use App\Domains\Integration\Services\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Read-only feed of the user's Google Calendar events for the calendar month
 * view. Fetched asynchronously by the frontend so the base calendar renders
 * instantly and Google events layer in on top — a calendar hiccup (not
 * connected, missing scope, API error) never blocks the page, it just yields
 * an empty list plus a connected/error hint the UI can act on.
 */
class CalendarGoogleEventsController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $start = $request->query('start', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end = $request->query('end', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $timeMin = Carbon::createFromFormat('Y-m-d', $start)->startOfDay();
        $timeMax = Carbon::createFromFormat('Y-m-d', $end)->endOfDay();

        $result = GoogleCalendarService::eventsForTeam(
            $user->current_team_id,
            $user->id,
            $timeMin,
            $timeMax,
            false,   // include all-day events for the calendar
        );

        $events = [];
        foreach ($result['events'] as $i => $ev) {
            $events[] = [
                'id' => 'google-'.$i,
                'planner_id' => null,
                'title' => $ev['title'],
                'start' => $ev['date'],           // Y-m-d bucket for the month grid
                'end' => null,
                'kind' => 'google',
                'status' => null,
                'total' => null,
                'source' => 'Google Calendar',
                'notes' => null,
                'completed_at' => null,
                'all_day' => $ev['all_day'],
                'time' => $ev['all_day'] ? null : substr($ev['start'], 11, 5),
            ];
        }

        return response()->json([
            'connected' => $result['connected'],
            'error' => $result['error'],
            'events' => $events,
        ]);
    }
}

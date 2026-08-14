<?php

namespace App\Domains\Integration\Services;

use App\Domains\Integration\Models\Integration;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Read-only bridge to the user's primary Google Calendar. Used by the routine
 * planner to flag time blocks that collide with real calendar events.
 *
 * Every failure mode (no integration, expired/insufficient token, API error)
 * is reported through the returned `connected`/`error` pair instead of throwing,
 * so the routine view can degrade gracefully — a calendar hiccup must never
 * take down the planner. `error` values the caller can act on:
 *   - null    -> all good
 *   - 'scope' -> connected, but the token predates the Calendar scope (reconnect)
 *   - 'reauth'-> token missing/expired and not refreshable (reconnect)
 *   - 'fetch' -> transient Google API failure
 */
class GoogleCalendarService
{
    /**
     * Timed events from the primary calendar between two instants.
     *
     * @return array{connected: bool, error: ?string, events: array<int, array{title: string, start: string, end: string}>}
     *         event start/end are 'Y-m-d H:i' in the event's own (local) offset.
     */
    public static function eventsForTeam(int $teamId, int $userId, DateTimeInterface $timeMin, DateTimeInterface $timeMax): array
    {
        $integration = Integration::where([
            'user_id' => $userId,
            'team_id' => $teamId,
            'name' => 'Google',
        ])->first();

        if (! $integration || ! $integration->token) {
            return ['connected' => false, 'error' => null, 'events' => []];
        }

        try {
            $client = GoogleService::getClient($integration->id);
        } catch (Exception $e) {
            // e.g. "Need authorize again" -- no usable refresh token.
            return ['connected' => false, 'error' => 'reauth', 'events' => []];
        }

        $token = $client->getAccessToken();
        $accessToken = is_array($token) ? ($token['access_token'] ?? null) : $token;
        if (! $accessToken) {
            return ['connected' => false, 'error' => 'reauth', 'events' => []];
        }

        try {
            $response = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/calendar/v3/calendars/primary/events', [
                    'timeMin' => $timeMin->format(DateTimeInterface::RFC3339),
                    'timeMax' => $timeMax->format(DateTimeInterface::RFC3339),
                    'singleEvents' => 'true',   // expand recurring events into instances
                    'orderBy' => 'startTime',
                    'maxResults' => 250,
                ]);
        } catch (Exception $e) {
            Log::warning('Google Calendar fetch failed: '.$e->getMessage(), ['team_id' => $teamId]);

            return ['connected' => true, 'error' => 'fetch', 'events' => []];
        }

        if ($response->status() === 403) {
            // Token authorized before Calendar scope existed -- needs re-consent.
            return ['connected' => true, 'error' => 'scope', 'events' => []];
        }
        if ($response->status() === 401) {
            return ['connected' => false, 'error' => 'reauth', 'events' => []];
        }
        if (! $response->successful()) {
            return ['connected' => true, 'error' => 'fetch', 'events' => []];
        }

        $events = [];
        foreach (($response->json('items') ?? []) as $ev) {
            if (($ev['status'] ?? '') === 'cancelled') {
                continue;
            }
            $start = $ev['start']['dateTime'] ?? null;   // timed events only
            $end = $ev['end']['dateTime'] ?? null;
            if (! $start || ! $end) {
                continue;                                 // all-day / date-only: no clash
            }
            $events[] = [
                'title' => $ev['summary'] ?? '(sin titulo)',
                'start' => Carbon::parse($start)->format('Y-m-d H:i'),
                'end' => Carbon::parse($end)->format('Y-m-d H:i'),
            ];
        }

        return ['connected' => true, 'error' => null, 'events' => $events];
    }
}

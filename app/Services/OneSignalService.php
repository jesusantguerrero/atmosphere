<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the OneSignal REST API.
 *
 * The frontend already initialises OneSignal in `app.blade.php`; this service
 * is the missing server-side piece that actually triggers the pushes. Used by
 * the shopping-list listener to ping the spouse when an item gets added or
 * the trip is reset — events that warrant interrupting them while they're
 * out of the app. Pure cycle-state changes stay Mercure-only (real-time
 * in-app); we never push for those because that's spam.
 */
class OneSignalService
{
    public const ENDPOINT = 'https://api.onesignal.com/notifications';

    /**
     * Send a push to a specific user identified by their Loger user_id.
     *
     * Relies on the frontend having called `OneSignal.login(<user_id>)` so
     * OneSignal links the subscription to that external user ID. Multiple
     * devices for the same user (laptop + phone) all receive the push.
     *
     * Returns false silently when credentials are missing — same contract as
     * `sendToTag`. Failures are logged, not thrown.
     */
    public function sendToUser(int|string $userId, string $heading, string $message, ?array $url = null): bool
    {
        $appId = config('services.onesignal.app_id');
        $apiKey = config('services.onesignal.rest_api_key');

        if (! $appId || ! $apiKey) {
            return false;
        }

        $payload = [
            'app_id' => $appId,
            'headings' => ['en' => $heading],
            'contents' => ['en' => $message],
            'include_aliases' => ['external_id' => [(string) $userId]],
            'target_channel' => 'push',
        ];

        if (isset($url['web'])) {
            $payload['web_url'] = $url['web'];
        }

        return $this->dispatch($payload, $apiKey);
    }

    /**
     * Send a push to every subscriber tagged `{key} == {value}`, optionally
     * excluding specific subscription IDs (used to suppress the "echo" push
     * back to the actor who just triggered the change).
     *
     * Returns false silently when credentials aren't configured so test +
     * dev environments don't blow up. Failures during the HTTP call are
     * logged, not thrown — a missed push must never break the originating
     * request flow (the user already saw their UI update via Mercure).
     *
     * @param  array<string, string>|null  $url  Optional `{web: ..., app: ...}` deep link.
     * @param  array<int, string>  $excludedSubscriptionIds  Subscriptions to skip.
     */
    public function sendToTag(string $key, string $value, string $heading, string $message, ?array $url = null, array $excludedSubscriptionIds = []): bool
    {
        $appId = config('services.onesignal.app_id');
        $apiKey = config('services.onesignal.rest_api_key');

        if (! $appId || ! $apiKey) {
            return false;
        }

        $payload = [
            'app_id' => $appId,
            'headings' => ['en' => $heading],
            'contents' => ['en' => $message],
            'filters' => [
                ['field' => 'tag', 'key' => $key, 'relation' => '=', 'value' => $value],
            ],
        ];

        if (isset($url['web'])) {
            $payload['web_url'] = $url['web'];
        }

        $cleanExcludes = array_values(array_filter(array_map('strval', $excludedSubscriptionIds)));
        if ($cleanExcludes) {
            $payload['excluded_subscription_ids'] = $cleanExcludes;
        }

        return $this->dispatch($payload, $apiKey);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function dispatch(array $payload, string $apiKey): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Basic {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post(self::ENDPOINT, $payload);

            if (! $response->successful()) {
                Log::warning('OneSignal push failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('OneSignal push threw', ['error' => $e->getMessage()]);

            return false;
        }
    }
}

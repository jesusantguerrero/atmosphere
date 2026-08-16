<?php

namespace App\Notifications\Channels;

use App\Services\OneSignalService;
use Illuminate\Notifications\Notification;

/**
 * Reusable OneSignal push channel for the Laravel notification system.
 *
 * Targets the notifiable by its OneSignal external ID (the Loger user_id the
 * frontend passed to `OneSignal.login(...)`), so a push reaches every device
 * that user has subscribed — no tags required. The notification supplies the
 * copy through `toOneSignal($notifiable)`; `LogerNotification` derives a
 * sensible default from `toArray()`, so most notifications need nothing extra.
 *
 * Mirrors the fail-soft contract of OneSignalService: a missing external ID,
 * missing credentials, or an API error never throws — a dropped push must not
 * break the originating request or console command. The in-app `database`
 * channel remains the source of truth.
 */
class OneSignalChannel
{
    public function __construct(private OneSignalService $oneSignal) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toOneSignal')) {
            return;
        }

        $payload = $notification->toOneSignal($notifiable);
        if (empty($payload) || empty($payload['message'])) {
            return;
        }

        $externalId = $this->externalIdFor($notifiable);
        if (! $externalId) {
            return;
        }

        $this->oneSignal->sendToUser(
            userId: $externalId,
            heading: $payload['heading'] ?? config('app.name', 'Loger'),
            message: $payload['message'],
            url: isset($payload['url']) ? ['web' => $payload['url']] : null,
        );
    }

    private function externalIdFor(object $notifiable): int|string|null
    {
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $route = $notifiable->routeNotificationFor('onesignal', null);
            if ($route) {
                return $route;
            }
        }

        return $notifiable->id ?? null;
    }
}

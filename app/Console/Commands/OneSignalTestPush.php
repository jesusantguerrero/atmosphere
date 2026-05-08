<?php

namespace App\Console\Commands;

use App\Services\OneSignalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Smoke-tests the OneSignal credentials and pipeline end-to-end. Sends a
 * single push to ALL subscribers (no tag filter) so we can verify the REST
 * key is valid and the device is registered without needing the full
 * shopping-list flow.
 *
 *   php artisan onesignal:test-push "Hello from Loger"
 */
class OneSignalTestPush extends Command
{
    protected $signature = 'onesignal:test-push {message? : Body of the push} {--tag-key=} {--tag-value=}';

    protected $description = 'Send a test OneSignal push (broadcast or tag-targeted) to verify integration.';

    public function handle(OneSignalService $service): int
    {
        $appId = config('services.onesignal.app_id');
        $apiKey = config('services.onesignal.rest_api_key');

        if (! $appId || ! $apiKey) {
            $this->error('Missing ONESIGNAL_APP_ID or ONESIGNAL_REST_API_KEY in env.');

            return self::FAILURE;
        }

        $this->info("Using app_id: {$appId}");
        $this->info('REST key present: '.(strlen($apiKey)).' chars');

        $message = $this->argument('message') ?? 'Test push from Loger ('.now()->toTimeString().')';
        $tagKey = $this->option('tag-key');
        $tagValue = $this->option('tag-value');

        // Tag-scoped path uses the production service. Broadcast path posts
        // directly because OneSignalService deliberately requires a tag.
        if ($tagKey && $tagValue) {
            $sent = $service->sendToTag($tagKey, $tagValue, 'Loger', $message);
            $this->line($sent ? '<info>Push sent to tag</info>' : '<error>Push failed (see laravel.log)</error>');

            return $sent ? self::SUCCESS : self::FAILURE;
        }

        $response = Http::withHeaders([
            'Authorization' => "Basic {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post(OneSignalService::ENDPOINT, [
            'app_id' => $appId,
            'headings' => ['en' => 'Loger'],
            'contents' => ['en' => $message],
            'included_segments' => ['Total Subscriptions'],
        ]);

        if ($response->successful()) {
            $body = $response->json();
            $this->info('Push accepted by OneSignal.');
            $this->line('  notification id: '.($body['id'] ?? 'n/a'));
            $this->line('  recipients:      '.($body['recipients'] ?? 'n/a'));

            return self::SUCCESS;
        }

        $this->error('OneSignal returned '.$response->status());
        $this->line($response->body());
        Log::warning('OneSignal test push failed', ['status' => $response->status(), 'body' => $response->body()]);

        return self::FAILURE;
    }
}

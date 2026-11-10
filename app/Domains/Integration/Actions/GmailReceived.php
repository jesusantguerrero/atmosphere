<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Services\GoogleService;
use App\Exceptions\TransactionInProgressException;
use App\Models\ProcessedEmail;
use Exception;
use Google\Service\Gmail as ServiceGmail;
use Illuminate\Support\Facades\Log;
use PhpMimeMailParser\Parser as EmailParser;

class GmailReceived implements AutomationActionContract
{
    const CONDITION_FROM = 'from';

    const CONDITION_TO = 'to';

    const CONDITION_SUBJECT = 'subject';

    const CONDITION_INCLUDES = 'includes';

    const CONDITION_CUSTOM = 'custom';

    /**
     * Minute-by-minute check: the newest threads of the bank query, processing
     * only messages newer than the last one seen (track.historyId). With
     * ['backfill' => true] it pages back through older threads instead.
     *
     * @return void
     */
    public static function handle(Automation $automation, $lastData = null, $task = null, $previousTask = null, $trigger = null)
    {
        $maxResults = 15;
        $track = json_decode($automation->track, true);
        $trackId = is_array($track) ? ($track['historyId'] ?? 0) : 0;
        $latestMailData = null;

        // Check if backfill is explicitly requested via command flag
        $backfillRequested = is_array($lastData) && ($lastData['backfill'] ?? false);

        // Continue backfill only if requested OR if there's an active pageToken from previous backfill
        $pageToken = is_array($track) ? ($track['pageToken'] ?? null) : null;
        $backfillMode = $backfillRequested || ($pageToken !== null);

        // If backfill was not requested but pageToken exists, clear it to exit backfill mode
        if (! $backfillRequested && $pageToken !== null) {
            $backfillMode = false;
            $pageToken = null;
        }

        $service = self::connect($automation);
        if (! $service) {
            return;
        }

        $queryOptions = self::query($trigger);
        if (is_array($lastData)) {
            if (! empty($lastData['startDate'])) {
                $queryOptions = trim($queryOptions.' after:'.str_replace('-', '/', $lastData['startDate']));
            }
            if (! empty($lastData['endDate'])) {
                $queryOptions = trim($queryOptions.' before:'.str_replace('-', '/', $lastData['endDate']));
            }
        }

        $listParams = ['maxResults' => $maxResults, 'q' => $queryOptions];
        if ($backfillMode && $pageToken) {
            $listParams['pageToken'] = $pageToken;
        }

        $results = $service->users_threads->listUsersThreads('me', $listParams);

        Integration::find($automation->integration_id)?->markSynced();

        foreach ($results->getThreads() as $thread) {
            $theadResponse = $service->users_threads->get('me', $thread->id, ['format' => 'METADATA']);
            foreach ($theadResponse->getMessages() as $message) {

                // In backfill mode: process all messages. In normal mode: only process new messages
                $shouldProcess = $backfillMode || ($message && $message->getHistoryId() > $trackId);

                if ($shouldProcess) {
                    $mail = self::processMessage($automation, $service, $message);
                    if ($mail === null) {
                        continue;
                    }

                    if ($latestMailData === null || $message->getHistoryId() > $latestMailData['historyId']) {
                        $latestMailData = $mail;
                    }

                    // Save track data
                    if ($backfillMode) {
                        // In backfill mode: store pageToken for next batch
                        $automation->track = json_encode(array_merge($latestMailData ?? [], [
                            'pageToken' => $results->getNextPageToken(),
                        ]));
                    } else {
                        // In normal mode: store latest mail data with historyId
                        $automation->track = json_encode($latestMailData);
                    }
                    $automation->save();
                }
            }
        }
    }

    /**
     * One short step of the catch-up sync (the «Sync now» button): one page of
     * bank emails since `$since`. Emails already imported are skipped before
     * downloading them. Call again with the returned page token until `done`.
     * When done, the minute check carries on from the newest email seen.
     *
     * @param  array<string, mixed>|null  $latest  newest email seen in previous steps
     * @return array{checked: int, processed: int, next_page_token: ?string, done: bool, latest: array<string, mixed>|null}
     */
    public static function syncChunk(Automation $automation, string $since, ?string $pageToken = null, ?array $latest = null, int $pageSize = 10): array
    {
        $service = self::connect($automation);
        if (! $service) {
            throw new \RuntimeException('Gmail is not connected for this automation.');
        }

        $params = ['maxResults' => $pageSize, 'q' => trim(self::query($automation->triggerTask).' after:'.str_replace('-', '/', $since))];
        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }
        $results = $service->users_threads->listUsersThreads('me', $params);
        $checked = 0;
        $processed = 0;

        foreach ($results->getThreads() ?? [] as $thread) {
            foreach ($service->users_threads->get('me', $thread->id, ['format' => 'METADATA'])->getMessages() as $message) {
                $checked++;
                if ($latest === null || $message->getHistoryId() > ($latest['historyId'] ?? 0)) {
                    $latest = ['id' => $message->id, 'threadId' => $message->threadId, 'historyId' => $message->historyId];
                }
                if (ProcessedEmail::wasProcessed((int) $automation->team_id, (string) $message->id)) {
                    continue;
                }
                if (self::processMessage($automation, $service, $message) !== null) {
                    $processed++;
                }
            }
        }

        $next = $results->getNextPageToken();
        if (! $next) {
            // Leave any half-done backfill behind and resume the minute check from the newest email.
            $track = json_decode($automation->track, true);
            if ($latest && (! is_array($track) || ($latest['historyId'] ?? 0) >= ($track['historyId'] ?? 0))) {
                $automation->track = json_encode($latest);
                $automation->save();
            }
            Integration::find($automation->integration_id)?->markSynced();
        }

        return ['checked' => $checked, 'processed' => $processed, 'next_page_token' => $next, 'done' => ! $next, 'latest' => $latest];
    }

    /**
     * Instance entry point for syncChunk(), so callers can swap it in tests.
     *
     * @param  array<string, mixed>|null  $latest
     * @return array{checked: int, processed: int, next_page_token: ?string, done: bool, latest: array<string, mixed>|null}
     */
    public function syncStep(Automation $automation, string $since, ?string $pageToken = null, ?array $latest = null): array
    {
        return self::syncChunk($automation, $since, $pageToken, $latest);
    }

    /**
     * The team's live Gmail connection. The automation may carry a null or stale
     * integration_id (created before Gmail was linked, or the integration was
     * reconnected); resolve the token-bearing one and self-heal the row.
     */
    private static function connect(Automation $automation): ?ServiceGmail
    {
        $integration = $automation->integration_id
            ? Integration::find($automation->integration_id)
            : null;
        if (! $integration || ! $integration->token) {
            $integration = GoogleService::findGoogleIntegration(
                (int) $automation->user_id,
                (int) $automation->team_id,
                true
            );
        }
        if (! $integration) {
            Log::warning('GmailReceived: no connected Gmail integration for automation', [
                'automation_id' => $automation->id,
            ]);

            return null;
        }
        if ((int) $automation->integration_id !== (int) $integration->id) {
            $automation->forceFill(['integration_id' => $integration->id])->save();
        }

        return new ServiceGmail(GoogleService::getClient($integration->id));
    }

    /**
     * Gmail search built from the trigger. Supports both formats:
     * 1. New: {"query": "from:(email1 OR email2)"} (Universal Bank Parser)
     * 2. Old: [{"conditionType":"from","value":"email"}] (legacy bank automations)
     */
    private static function query($trigger): string
    {
        $taskConditions = json_decode($trigger?->values ?? 'null');

        if (is_object($taskConditions) && isset($taskConditions->query)) {
            return (string) $taskConditions->query;
        }

        $conditions = [];
        if (is_array($taskConditions)) {
            foreach ($taskConditions as $taskCondition) {
                if (isset($taskCondition->conditionType) && $taskCondition->value) {
                    $conditions[] = "$taskCondition->conditionType:$taskCondition->value";
                }
            }
        }

        return implode(' ', $conditions);
    }

    /**
     * Run one email through the automation's tasks. Returns the email's summary
     * (for the track), or null when it could not be processed.
     *
     * @return array{from: mixed, subject: mixed, messageId: mixed, id: string, threadId: string, historyId: mixed, date: mixed}|null
     */
    private static function processMessage(Automation $automation, ServiceGmail $service, $message): ?array
    {
        try {
            $raw = $service->users_messages->get('me', $message->id, ['format' => 'raw']);
            $parser = self::parseEmail($raw);

            $mail = [
                'from' => $parser->getHeader('from'),
                'subject' => $parser->getHeader('subject'),
                'messageId' => $parser->getHeader('message-id'),
                'id' => $message->id,
                'threadId' => $message->threadId,
                'historyId' => $message->historyId,
                'date' => $parser->getHeader('date'),
            ];

            $payload = [...$mail, 'message' => $parser->getMessageBody('html')];
            $tasks = $automation->tasks;
            $previousTask = $tasks->first();
            foreach ($tasks as $taskIndex => $task) {
                if ($taskIndex !== 0) {
                    self::say("running workflow-task:$task->id:$task->name");
                    if (! $payload) {
                        // A task returned null (e.g. transaction in progress): skip the rest for this email.
                        self::say('payload is null, skipping remaining tasks for this email');
                        break;
                    }
                    $payload = $task->entity::handle($automation, $payload, $task, $previousTask, $tasks[0]);
                    $previousTask = $task;
                }
            }

            return $mail;
        } catch (TransactionInProgressException $e) {
            // Transaction is in progress - this is expected, skip without error logging
            self::say('transaction in progress, skipping remaining tasks for this email');

            return null;
        } catch (Exception $e) {
            Log::error($e->getMessage(), [$e]);

            return null;
        }
    }

    /**
     * Progress lines for `php artisan app:automation-check`; silent on web requests.
     */
    private static function say(string $line): void
    {
        if (app()->runningInConsole()) {
            echo "\n {$line}";
        }
    }

    public function getName(): string
    {
        return 'gmailReceived';
    }

    public function label(): string
    {
        return 'New Gmail';
    }

    public function getDescription(): string
    {
        return 'When a new email is received';
    }

    public static function parseEmail($raw)
    {
        $switched = str_replace(['-', '_'], ['+', '/'], $raw['raw']);
        $raw = base64_decode($switched);

        return (new EmailParser)->setText($raw);
    }
}

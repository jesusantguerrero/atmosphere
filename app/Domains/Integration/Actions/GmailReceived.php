<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Services\GoogleService;
use App\Exceptions\TransactionInProgressException;
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
     * Validate and create a new team for the given user.
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

        $taskConditions = json_decode($trigger->values);
        $client = GoogleService::getClient($automation->integration_id);
        $service = new ServiceGmail($client);

        // Support both query formats:
        // 1. New format: {"query": "from:(email1 OR email2)"}
        // 2. Old format: [{"conditionType":"from","value":"email"}]
        if (is_object($taskConditions) && isset($taskConditions->query)) {
            // New query string format (Universal Bank Parser)
            $queryOptions = $taskConditions->query;
        } else {
            // Old conditions array format (legacy bank-specific automations)
            $conditions = [];
            if (is_array($taskConditions)) {
                foreach ($taskConditions as $taskCondition) {
                    if (isset($taskCondition->conditionType) && $taskCondition->value) {
                        $conditions[] = "$taskCondition->conditionType:$taskCondition->value";
                    }
                }
            }
            $queryOptions = count($conditions) ? implode(' ', $conditions) : '';
        }

        $listParams = ['maxResults' => $maxResults, 'q' => $queryOptions];
        if ($backfillMode && $pageToken) {
            $listParams['pageToken'] = $pageToken;
        }

        $results = $service->users_threads->listUsersThreads('me', $listParams);

        foreach ($results->getThreads() as $thread) {
            $theadResponse = $service->users_threads->get('me', $thread->id, ['format' => 'METADATA']);
            foreach ($theadResponse->getMessages() as $message) {

                // In backfill mode: process all messages. In normal mode: only process new messages
                $shouldProcess = $backfillMode || ($message && $message->getHistoryId() > $trackId);

                if ($shouldProcess) {
                    try {
                        $raw = $service->users_messages->get('me', $message->id, ['format' => 'raw']);
                        $parser = self::parseEmail($raw);

                        $body = $parser->getMessageBody('html');
                        $mail = [
                            'from' => $parser->getHeader('from'),
                            'subject' => $parser->getHeader('subject'),
                            'messageId' => $parser->getHeader('message-id'),
                            'id' => $message->id,
                            'threadId' => $message->threadId,
                            'historyId' => $message->historyId,
                            'date' => $parser->getHeader('date'),
                        ];

                        $mail['message'] = $body;
                        $payload = $mail;
                        $tasks = $automation->tasks;
                        $previousTask = $tasks->first();
                        foreach ($tasks as $taskIndex => $task) {
                            if ($taskIndex !== 0) {
                                $actionEntity = $task->entity;
                                echo "\n running workflow-task:$task->id:$task->name";
                                if (! $payload) {
                                    // Payload is null - task returned null (e.g., transaction in progress)
                                    // Skip remaining tasks for this email and move to next email
                                    echo "\n payload is null, skipping remaining tasks for this email";
                                    break;
                                }
                                $payload = $actionEntity::handle($automation, $payload, $task, $previousTask, $tasks[0]);
                                $previousTask = $task;
                            }
                        }

                        if ($latestMailData === null || $message->getHistoryId() > $latestMailData['historyId']) {
                            $latestMailData = [
                                'from' => $mail['from'],
                                'subject' => $mail['subject'],
                                'messageId' => $mail['messageId'],
                                'id' => $mail['id'],
                                'threadId' => $mail['threadId'],
                                'historyId' => $mail['historyId'],
                                'date' => $mail['date'],
                            ];
                        }

                        // Save track data
                        if ($backfillMode) {
                            // In backfill mode: store pageToken for next batch
                            $nextPageToken = $results->getNextPageToken();
                            $automation->track = json_encode(array_merge($latestMailData ?? [], [
                                'pageToken' => $nextPageToken,
                            ]));
                        } else {
                            // In normal mode: store latest mail data with historyId
                            $automation->track = json_encode($latestMailData);
                        }
                        $automation->save();
                    } catch (TransactionInProgressException $e) {
                        // Transaction is in progress - this is expected, skip without error logging
                        echo "\n transaction in progress, skipping remaining tasks for this email";

                        continue;
                    } catch (Exception $e) {
                        Log::error($e->getMessage(), [$e]);

                        continue;
                    }
                }
            }
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

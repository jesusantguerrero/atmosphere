<?php

namespace App\Domains\Integration\Actions;

use Illuminate\Support\Facades\Log;
use Google\Service\Gmail as ServiceGmail;
use PhpMimeMailParser\Parser as EmailParser;
use App\Domains\Automation\Models\Automation;
use App\Domains\Transaction\Services\BHDService;
use App\Domains\Integration\Services\GoogleService;
use App\Domains\Automation\Concerns\AutomationActionContract;

class GmailReceived implements AutomationActionContract
{
    /**
     * Validate and create a new team for the given user.
     *
     * @return void
     */
    public static function handle(Automation $automation, $lastData = null, $task = null, $previousTask = null, $trigger = null)
    {
        $maxResults = 15;
        $track = json_decode($automation->track, true);
        $trackId = $track['historyId'] ?? 0;
        $config = json_decode($trigger->values);
        $client = GoogleService::getClient($automation->integration_id);
        $service = new ServiceGmail($client);
        $notification = BHDService::EMAIL_NOTIFICATION;
        $condition = isset($config->conditionType) && $config->value ? "$config->conditionType:$config->value" : '';
        if (! $condition) {
            $condition = $config->value ?? '';
        }
        $results = $service->users_threads->listUsersThreads('me', ['maxResults' => $maxResults, 'q' => "$condition"]);

        foreach ($results->getThreads() as $index => $thread) {
            $theadResponse = $service->users_threads->get('me', $thread->id, ['format' => 'MINIMAL']);
            foreach ($theadResponse->getMessages() as $message) {
                if ($message && $message->getHistoryId() > $trackId) {
                    $raw = $service->users_messages->get('me', $message->id, ['format' => 'raw']);
                    $parser = self::parseEmail($raw);

                    $body = $parser->getMessageBody('html');
                    $mail = [
                        'index' => $index,
                        'from' => $parser->getHeader('from'),
                        'subject' => $parser->getHeader('subject'),
                        'messageId' => $parser->getHeader('message-id'),
                        'id' => $message->id,
                        'threadId' => $message->threadId,
                        'historyId' => $message->historyId,
                        'date' => $parser->getHeader('date'),
                    ];

                    if ($index == 0) {
                        $automation->track = json_encode($mail);
                        $automation->save();
                    }

                    $mail['message'] = $body;
                    $payload = $mail;
                    $tasks = $automation->tasks;
                    $previousTask = $tasks->first();
                    foreach ($tasks as $taskIndex => $task) {
                        if ($taskIndex !== 0) {
                            $actionEntity = $task->entity;
                            try {
                                $payload = $actionEntity::handle($automation, $payload, $task, $previousTask, $tasks[0]);
                                $previousTask = $task;
                            } catch (\Exception $e) {
                                Log::error($e->getMessage(), $mail);
                                continue;
                            }
                        }
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

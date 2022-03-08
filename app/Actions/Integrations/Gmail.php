<?php

namespace App\Actions\Integrations;

use App\Libraries\GoogleService;
use App\Models\Integrations\Automation;
use Google_Service_Gmail;
use Illuminate\Support\Facades\Log;
use PhpMimeMailParser\Parser as EmailParser;

class Gmail
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  Automation  $automation
     * @return void
     */
    public static function received(Automation $automation)
    {
        $maxResults = 50;
        $track = json_decode($automation->track, true);
        $track['historyId'] = $track['historyId'] ?? 0;
        $trigger = $automation->trigger()->first();
        $config = json_decode($trigger->values);
        $client = GoogleService::getClient($automation->integration_id);
        $service = new Google_Service_Gmail($client);
        $condition = isset($config->conditionType) && $config->value ? "$config->conditionType($config->value)" : "";
        if (!$condition) {
            $condition = $config->value ?? "";
        }

        $historyId = isset($track['historyId']) ? $track['historyId'] : 0;
        $results = $service->users_threads->listUsersThreads("me", ['maxResults' => $maxResults, 'q' => "$condition"], ['startHistoryId' => $historyId]);
        foreach ($results->getThreads() as $index => $thread) {
            echo "reading thread $index \n";
            $theadResponse = $service->users_threads->get("me", $thread->id, ['format' => 'MINIMAL']);
            foreach ($theadResponse->getMessages() as $message) {
                if ($message) {
                    $raw = $service->users_messages->get("me", $message->id, ['format' => 'raw']);
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
                            $action = $task->name;
                            $actionEntity = $task->entity;
                            try {
                                $payload = $actionEntity::$action($automation, $payload, $task, $previousTask, $tasks[0]);
                                $previousTask = $task;
                            } catch (\Exception $e) {
                                print_r($e->getMessage());
                                Log::error($e->getMessage(), $mail);
                                continue;
                            }
                        }
                    }
                }
            }
        };
    }

    public static function parseEmail($raw)
    {
        $switched = str_replace(['-', '_'], ['+', '/'], $raw['raw']);
        $raw = base64_decode($switched);
        return (new EmailParser)->setText($raw);
    }
}

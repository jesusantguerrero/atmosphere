<?php

namespace App\Actions\Integrations;

use App\Libraries\GoogleService;
use App\Models\Integrations\Automation;
use Google_Service_Gmail;
use Illuminate\Support\Facades\Log;
use PhpMimeMailParser\Parser as EmailParser;

class ReadGmail
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  Automation  $automation
     * @return void
     */
    public static function create(Automation $automation)
    {
        $maxResults = 50;
        $track = json_decode($automation->track, true);
        $track['historyId'] = $track['historyId'] ?? 0;
        $config = json_decode($automation->config);
        $client = GoogleService::getClient($automation->integration_id);
        $service = new Google_Service_Gmail($client);
        $condition = isset($config->condition) && $config->value ? "$config->condition($config->value)" : "";
        if (!$condition) {
            $condition = $config->value ?? "";
        }
        $results = $service->users_threads->listUsersThreads("me", ['maxResults' => $maxResults, 'q' => "$condition"]);

        foreach ($results->getThreads() as $index => $thread) {
            $theadResponse = $service->users_threads->get("me", $thread->id, ['format' => 'MINIMAL']);
            $message = $theadResponse->getMessages()[0];
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
                $actionEntity = $automation->action;
                $action = $automation->name;
                try {
                    $actionEntity::$action($automation, $mail, $config);
                } catch (\Exception $e) {
                    Log::error($e->getMessage(), $mail);
                    continue;
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

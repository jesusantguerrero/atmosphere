<?php

namespace App\Domains\Integration\Actions;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Models\Team;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Services\PlanService;

/**
 * Automation action: turn a received email into a task card.
 *
 * Chains after GmailReceived — that action pipes each matching email
 * (from / subject / message / date / messageId) into here, and we drop it as a
 * PlanItem on a board the user then triages. This is the "emails -> tasks"
 * counterpart to the bank pipeline: instead of parsing money out of an email,
 * it captures the email itself as something to act on — the calendar/task side
 * of automation the finance flow never covered.
 *
 * Deduped by the email's message-id so re-syncs never create duplicate cards.
 * Returns the payload unchanged so any later tasks in the chain still run.
 */
class CreateTaskFromEmail implements AutomationActionContract
{
    public static function handle(
        Automation $automation,
        mixed $payload,
        AutomationTaskAction $task,
        AutomationTaskAction $previousTask,
        AutomationTaskAction $trigger
    ) {
        if (! is_array($payload)) {
            return $payload;
        }

        $from = (string) ($payload['from'] ?? '');
        $subject = trim((string) ($payload['subject'] ?? ''));
        $messageId = (string) ($payload['messageId'] ?? ($payload['id'] ?? ''));
        $date = (string) ($payload['date'] ?? '');
        $snippet = self::preview((string) ($payload['message'] ?? ''));

        if ($messageId === '') {
            return $payload;   // nothing to dedupe on — skip rather than duplicate
        }

        // Which board the cards land on is configurable per automation; default
        // to a dedicated "Email" board the user triages.
        $values = json_decode($task->values ?? '{}', true);
        $boardName = is_array($values) && ! empty($values['board']) ? $values['board'] : 'Email';

        $team = Team::find($automation->team_id);
        if (! $team) {
            return $payload;
        }

        $board = (new PlanService)->findOrCreateBySlug($team, $boardName);
        $stage = $board->stages->first();
        if (! $stage) {
            return $payload;
        }

        $eventData = [
            'title' => $subject !== '' ? $subject : ($from !== '' ? $from : 'Email'),
            'board_id' => $stage->board_id,
            'stage_id' => $stage->id,
            'team_id' => $stage->team_id,
            'user_id' => $stage->user_id,
            'resource_id' => $messageId,
            'resource_origin' => 'email',
            'resource_type' => 'gmail',
            'fields' => [
                ['name' => 'from', 'type' => 'text', 'value' => $from],
                ['name' => 'subject', 'type' => 'text', 'value' => $subject],
                ['name' => 'date', 'type' => 'text', 'value' => $date],
                ['name' => 'snippet', 'type' => 'text', 'value' => $snippet],
                ['name' => 'messageId', 'type' => 'text', 'value' => $messageId],
            ],
        ];

        // Deduped by message-id within the board's stage: a re-sync updates the
        // existing card instead of adding a second one.
        PlanItem::createEvent($eventData, [
            'resource_id' => $messageId,
            'stage_id' => $stage->id,
        ]);

        return $payload;
    }

    /** Short plain-text preview from the (HTML) email body. */
    private static function preview(string $html, int $limit = 240): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));

        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit).'…' : $text;
    }

    public function getName(): string
    {
        return 'createTaskFromEmail';
    }

    public function getDescription(): string
    {
        return 'Create a task from a received email';
    }

    public function label(): string
    {
        return 'Create task from email';
    }
}

<?php

namespace App\Domains\Integration\Actions\Whatsapp;

use App\Models\Automation;
use App\Models\Team;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Services\PlanService;

class CreateTaskFromMessage
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  Automation  $automation
     * @param  Google_Calendar_Events  $calendarEvents
     * @return void
     */
    public static function create(Team $team, string $from, string $message, string $type, $boardName = 'whatsapp')
    {
        $board = (new PlanService)->findOrCreateBySlug($team, $boardName);
        $stage = $board->stages[0];
        // `plan_id` is the real column (there is no `board_id` on plan_items) and
        // fields are saved after the row exists — same fix as CreateTaskFromEmail.
        $item = PlanItem::firstOrNew([
            'resource_id' => $from.$message,
            'resource_origin' => 'whatsapp',
            'stage_id' => $stage->id,
        ]);

        $item->fill([
            'title' => $from,
            'plan_id' => $stage->plan_id,
            'user_id' => $stage->user_id,
            'stage_id' => $stage->id,
            'team_id' => $stage->team_id,
            'resource_id' => $from,
            'resource_origin' => 'message',
            'resource_type' => 'whatsapp',
        ])->save();

        $item->saveFields([
            ['name' => 'message', 'type' => 'text', 'value' => $message],
            ['name' => 'sender', 'type' => 'text', 'value' => $from],
            ['name' => 'sender', 'type' => 'text', 'value' => $type],
        ]);
    }
}

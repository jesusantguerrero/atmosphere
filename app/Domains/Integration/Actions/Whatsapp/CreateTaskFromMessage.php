<?php

namespace App\Domains\Integration\Actions\Whatsapp;

use App\Models\Team;
use App\Models\Automation;
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
    public static function create(Team $team, string $from, string $message, string $type, $boardName = "whatsapp")
    {
        $board = (new PlanService())->findOrCreateBySlug($team, $boardName);
        $stage = $board->stages[0];
        $item = [
            'title' => $from,
            'board_id' => $stage->board_id,
            'user_id' => $stage->user_id,
            'stage_id' => $stage->id,
            'team_id' => $stage->team_id,
            "resource_id" => $from,
            "resource_origin" => 'message',
            "resource_type" => 'whatsapp',
            'fields' => [
                ['name' => 'message', 'type' => 'text', 'value' => $message],
                ['name' => 'sender', 'type'=> 'text', 'value' => $from],
                ['name' => 'sender', 'type' => 'text', 'value' => $type],
            ]
        ];
        PlanItem::createEvent($item, [
            "resource_id" => $from.$message,
            "resource_origin" => 'whatsapp',
            "stage_id" => $stage->id
        ]);
    }

}

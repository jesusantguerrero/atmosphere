<?php

namespace App\Notifications;

/**
 * A guest who sees nothing yet asks the household's owner and administrators to share something.
 */
class GuestAccessRequested extends LogerNotification
{
    public function __construct(public int $teamId, public int $guestId, public string $guestName, public string $teamName) {}

    public function toArray($notifiable): array
    {
        return [
            'message' => __(':name asks to see something in :team. Choose which profiles to share.', ['name' => $this->guestName, 'team' => $this->teamName]),
            'cta' => __('Share profiles'),
            'link' => route('teams.show', $this->teamId, false),
            'team_id' => $this->teamId,
            'guest_id' => $this->guestId,
        ];
    }
}

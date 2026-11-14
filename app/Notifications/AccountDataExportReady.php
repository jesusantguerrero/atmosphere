<?php

namespace App\Notifications;

use App\Domains\AppCore\Services\AccountDataExporter;

class AccountDataExportReady extends LogerNotification
{
    public function __construct(public string $exportId) {}

    public function toArray($notifiable): array
    {
        return [
            'message' => __('Your data export is ready. The link works for :days days.', ['days' => AccountDataExporter::DAYS_KEPT]),
            'cta' => __('Download my data'),
            'link' => route('user.data-export.show', $this->exportId, false),
        ];
    }
}

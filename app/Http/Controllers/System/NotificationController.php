<?php

namespace App\Http\Controllers\System;
class NotificationController
{
    public function index()
    {
        return inertia('System/Notifications/Index', [
            'notifications' => request()->user()->unreadNotifications,
        ]);
    }

    public function update($notificationId)
    {
        $user = request()->user();
        $user->unreadNotifications()->where('id', $notificationId)->update(['read_at' => now()]);
    }

    public function bulkUpdate()
    {
        $user = request()->user();
        $user->unreadNotifications()->update(['read_at' => now()]);
    }
}

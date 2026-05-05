<?php

namespace App\Http\Controllers\System;

use Illuminate\Http\Request;

class NotificationController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->query('filter', 'unread');

        $query = $filter === 'all'
            ? $user->notifications()
            : $user->unreadNotifications();

        $notifications = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return inertia('System/Notifications/Index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
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

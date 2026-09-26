<?php

namespace App\Http\Controllers\System;

use App\Domains\Transaction\Models\BillingCycle;
use App\Models\User;
use App\Notifications\BillingCycleCutAlert;
use Illuminate\Http\Request;

class NotificationController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->query('filter', 'unread');
        $this->resolvePaidCreditCardAlerts($user);

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

    private function resolvePaidCreditCardAlerts(User $user): void
    {
        $alerts = $user->unreadNotifications()
            ->where('type', BillingCycleCutAlert::class)
            ->get(['id', 'data']);

        if ($alerts->isEmpty()) {
            return;
        }

        $cycleIds = $alerts->pluck('data.billing_cycle_id')->filter()->all();
        $accountIds = $alerts->pluck('data.link')
            ->map(function ($link): ?int {
                return preg_match('#^/finance/accounts/(\d+)$#', (string) $link, $matches)
                    ? (int) $matches[1]
                    : null;
            })
            ->filter()
            ->all();

        $paidCycles = BillingCycle::query()
            ->whereIn('status', [BillingCycle::STATUS_PAID, BillingCycle::STATUS_CANCELLED])
            ->where(function ($query) use ($cycleIds, $accountIds): void {
                $query->whereIn('id', $cycleIds)->orWhereIn('account_id', $accountIds);
            })
            ->get(['id', 'account_id', 'end_at']);

        $resolvedIds = $alerts->filter(function ($alert) use ($paidCycles): bool {
            $cycleId = $alert->data['billing_cycle_id'] ?? null;
            if ($cycleId) {
                return $paidCycles->contains('id', $cycleId);
            }

            $link = $alert->data['link'] ?? '';
            $message = $alert->data['message'] ?? '';

            return $paidCycles->contains(fn (BillingCycle $cycle) => $link === "/finance/accounts/{$cycle->account_id}"
                && str_ends_with($message, 'Due date: '.$cycle->end_at));
        })->pluck('id');

        if ($resolvedIds->isNotEmpty()) {
            $user->unreadNotifications()->whereIn('id', $resolvedIds)->update(['read_at' => now()]);
        }
    }
}

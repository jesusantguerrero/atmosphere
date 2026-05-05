<?php

namespace Tests\Feature\System;

use App\Models\User;
use App\Notifications\WatchlistThresholdAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Locks down the notifications page contract after the UX rewrite:
 * - Paginated payload shape
 * - filter prop (unread|all)
 * - unreadCount prop drives the page title and tab badge
 */
class NotificationsIndexTest extends TestCase
{
    use RefreshDatabase;

    private function seedNotification(User $user, ?string $readAt = null): void
    {
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => WatchlistThresholdAlert::class,
            'notifiable_type' => $user::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['message' => 'Test', 'cta' => 'See', 'link' => '/finance/watchlist/1']),
            'read_at' => $readAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_index_exposes_pagination_filter_and_unread_count(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Notifications/Index')
                ->has('notifications.data')
                ->has('notifications.current_page')
                ->has('notifications.last_page')
                ->where('filter', 'unread')
                ->where('unreadCount', 0)
            );
    }

    public function test_unread_filter_excludes_read_notifications(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->seedNotification($user); // unread
        $this->seedNotification($user, readAt: now()->toDateTimeString()); // read

        $this->actingAs($user)
            ->get('/notifications?filter=unread')
            ->assertInertia(fn (Assert $page) => $page
                ->where('unreadCount', 1)
                ->where('notifications.total', 1)
            );
    }

    public function test_all_filter_includes_read_and_unread(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->seedNotification($user);
        $this->seedNotification($user, readAt: now()->toDateTimeString());

        $this->actingAs($user)
            ->get('/notifications?filter=all')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filter', 'all')
                ->where('notifications.total', 2)
            );
    }

    public function test_unread_count_independent_of_filter(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->seedNotification($user);
        $this->seedNotification($user);
        $this->seedNotification($user, readAt: now()->toDateTimeString());

        // Count must reflect unread regardless of which tab is shown.
        $this->actingAs($user)
            ->get('/notifications?filter=all')
            ->assertInertia(fn (Assert $page) => $page->where('unreadCount', 2));
    }
}

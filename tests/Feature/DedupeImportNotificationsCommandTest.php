<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EntryGenerated;
use App\Notifications\TransactionsImported;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DedupeImportNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_keeps_only_the_most_recent_unread_entry_generated_notification(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        // Simulate the spam: 5 unread EntryGenerated notifications.
        for ($i = 0; $i < 5; $i++) {
            $user->notify(new EntryGenerated);
        }

        $this->assertEquals(5, $user->unreadNotifications()
            ->where('type', EntryGenerated::class)->count());

        $this->artisan('notifications:dedupe-imports')->assertSuccessful();

        $this->assertEquals(1, $user->fresh()->unreadNotifications()
            ->where('type', EntryGenerated::class)->count());
    }

    /** @test */
    public function it_is_idempotent(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        for ($i = 0; $i < 3; $i++) {
            $user->notify(new EntryGenerated);
        }

        $this->artisan('notifications:dedupe-imports')->assertSuccessful();
        $this->artisan('notifications:dedupe-imports')->assertSuccessful();

        $this->assertEquals(1, $user->fresh()->unreadNotifications()
            ->where('type', EntryGenerated::class)->count());
    }

    /** @test */
    public function dry_run_does_not_mark_anything_read(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        for ($i = 0; $i < 4; $i++) {
            $user->notify(new EntryGenerated);
        }

        $this->artisan('notifications:dedupe-imports', ['--dry' => true])->assertSuccessful();

        $this->assertEquals(4, $user->fresh()->unreadNotifications()
            ->where('type', EntryGenerated::class)->count());
    }

    /** @test */
    public function it_leaves_single_unread_notifications_alone(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->notify(new EntryGenerated);

        $this->artisan('notifications:dedupe-imports')->assertSuccessful();

        $this->assertEquals(1, $user->fresh()->unreadNotifications()
            ->where('type', EntryGenerated::class)->count());
    }

    /** @test */
    public function it_dedupes_transactions_imported_type_too(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        for ($i = 0; $i < 3; $i++) {
            $user->notify(new TransactionsImported('/finance/transactions'));
        }

        $this->artisan('notifications:dedupe-imports')->assertSuccessful();

        $this->assertEquals(1, $user->fresh()->unreadNotifications()
            ->where('type', TransactionsImported::class)->count());
    }
}

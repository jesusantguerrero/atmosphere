<?php

namespace Tests\Feature\Finance;

use App\Domains\Transaction\Models\BillingCycle;
use App\Models\User;
use App\Services\OneSignalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `loger:nudge-credit-cards` — three triggers, single-fire per cycle.
 * Tests pin down exact trigger windows + idempotency + status filter.
 */
class NudgeCreditCardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.onesignal.app_id' => 'test-app-id',
            'services.onesignal.rest_api_key' => 'test-rest-key',
        ]);
        Http::fake([OneSignalService::ENDPOINT => Http::response(['id' => 'notif'], 200)]);
    }

    private function makeCycle(User $owner, array $overrides = []): BillingCycle
    {
        $team = $owner->ownedTeams()->first();

        // saveQuietly bypasses the BillingCycle::saving boot hook that
        // recomputes `paid`/`status` from linked payments — without that
        // bypass, every test cycle would land back as PENDING/0 paid.
        $cycle = new BillingCycle;
        $cycle->forceFill(array_merge([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'account_id' => 1,
            'start_at' => Carbon::today()->subDays(20),
            'end_at' => Carbon::today()->subDay(),
            'due_at' => Carbon::today()->addDays(20),
            'total' => 5000,
            'subtotal' => 5000,
            'discounts' => 0,
            'paid' => 0,
            'debt' => 5000,
            'status' => BillingCycle::STATUS_PENDING,
        ], $overrides));
        $cycle->saveQuietly();

        return $cycle->fresh();
    }

    public function test_post_cut_fires_day_after_end_at(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $cycle = $this->makeCycle($owner, [
            'end_at' => Carbon::today()->subDay(),       // cut yesterday
            'due_at' => Carbon::today()->addDays(20),    // not in pre-due window
        ]);

        $this->artisan('loger:nudge-credit-cards')->assertSuccessful();

        Http::assertSent(fn ($req) => str_contains($req->data()['contents']['en'], 'Confirm the new balance'));
        $this->assertNotNull($cycle->fresh()->nudged_post_cut_at);
        $this->assertNull($cycle->fresh()->nudged_pre_due_at);
    }

    public function test_post_cut_does_not_double_fire(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $this->makeCycle($owner, [
            'end_at' => Carbon::today()->subDay(),
            'due_at' => Carbon::today()->addDays(20),
        ]);

        $this->artisan('loger:nudge-credit-cards')->assertSuccessful();
        Http::assertSentCount(1);

        $this->artisan('loger:nudge-credit-cards')->assertSuccessful();
        Http::assertSentCount(1); // still 1, not 2
    }

    public function test_pre_due_fires_three_days_before(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $cycle = $this->makeCycle($owner, [
            'end_at' => Carbon::today()->subDays(15),     // post-cut already past, but we'll mark it sent
            'due_at' => Carbon::today()->addDays(3),
            'nudged_post_cut_at' => Carbon::yesterday(),
        ]);

        $this->artisan('loger:nudge-credit-cards')->assertSuccessful();

        Http::assertSent(fn ($req) => str_contains($req->data()['contents']['en'], 'Due'));
        $this->assertNotNull($cycle->fresh()->nudged_pre_due_at);
    }

    public function test_pre_due_does_not_fire_after_due_date(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $cycle = $this->makeCycle($owner, [
            'end_at' => Carbon::today()->subDays(20),
            'due_at' => Carbon::yesterday(),              // already past due
            'nudged_post_cut_at' => Carbon::yesterday(),
        ]);

        $this->artisan('loger:nudge-credit-cards')->assertSuccessful();

        $this->assertNull($cycle->fresh()->nudged_pre_due_at);
    }

    public function test_overdue_fires_ten_days_after_due_date(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $cycle = $this->makeCycle($owner, [
            'end_at' => Carbon::today()->subDays(30),
            'due_at' => Carbon::today()->subDays(10),
            'status' => BillingCycle::STATUS_PENDING,
            'nudged_post_cut_at' => Carbon::today()->subDays(29),
            'nudged_pre_due_at' => Carbon::today()->subDays(13),
        ]);

        $this->artisan('loger:nudge-credit-cards')->assertSuccessful();

        Http::assertSent(fn ($req) => str_contains($req->data()['contents']['en'], 'log it now'));
        $this->assertNotNull($cycle->fresh()->nudged_overdue_at);
    }

    public function test_overdue_skips_when_partially_paid(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $cycle = $this->makeCycle($owner, [
            'end_at' => Carbon::today()->subDays(30),
            'due_at' => Carbon::today()->subDays(10),
            'status' => BillingCycle::STATUS_PARTIALLY_PAID,
            'paid' => 1000,
            'debt' => 4000,
            'nudged_post_cut_at' => Carbon::today()->subDays(29),
            'nudged_pre_due_at' => Carbon::today()->subDays(13),
        ]);

        $this->artisan('loger:nudge-credit-cards')->assertSuccessful();

        $this->assertNull($cycle->fresh()->nudged_overdue_at);
        Http::assertNothingSent();
    }

    public function test_paid_cycles_are_excluded_entirely(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $this->makeCycle($owner, [
            'end_at' => Carbon::today()->subDay(), // would normally trigger post-cut
            'status' => BillingCycle::STATUS_PAID,
            'paid' => 5000,
            'debt' => 0,
        ]);

        $this->artisan('loger:nudge-credit-cards')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_push_targets_team_owner_via_external_id(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $this->makeCycle($owner, [
            'end_at' => Carbon::today()->subDay(),
            'due_at' => Carbon::today()->addDays(20),
        ]);

        $this->artisan('loger:nudge-credit-cards')->assertSuccessful();

        Http::assertSent(function ($req) use ($owner) {
            $aliases = $req->data()['include_aliases']['external_id'] ?? [];

            return $aliases === [(string) $owner->id];
        });
    }
}

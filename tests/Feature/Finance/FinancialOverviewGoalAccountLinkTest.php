<?php

namespace Tests\Feature\Finance;

use App\Http\Controllers\Finance\FinancialOverviewController;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialOverviewGoalAccountLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_persists_goal_account_links(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $response = $this->postJson('/trends/financial-overview/goal-account-links', [
            'links' => [
                'fund-1' => [42, 43],
                'target-7' => [99],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $setting = Setting::where([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => FinancialOverviewController::SETTING_GOAL_ACCOUNT_LINKS,
        ])->firstOrFail();

        $this->assertEquals(
            ['fund-1' => [42, 43], 'target-7' => [99]],
            json_decode($setting->value, true)
        );
    }

    public function test_endpoint_drops_goals_with_empty_account_lists(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/trends/financial-overview/goal-account-links', [
            'links' => [
                'fund-1' => [42],
                'fund-2' => [],
            ],
        ])->assertOk();

        $setting = Setting::where([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => FinancialOverviewController::SETTING_GOAL_ACCOUNT_LINKS,
        ])->firstOrFail();

        $this->assertEquals(['fund-1' => [42]], json_decode($setting->value, true));
    }

    public function test_endpoint_deduplicates_account_ids(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/trends/financial-overview/goal-account-links', [
            'links' => [
                'fund-1' => [42, 42, 43],
            ],
        ])->assertOk();

        $setting = Setting::where([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => FinancialOverviewController::SETTING_GOAL_ACCOUNT_LINKS,
        ])->firstOrFail();

        $this->assertEquals(['fund-1' => [42, 43]], json_decode($setting->value, true));
    }

    public function test_endpoint_accepts_empty_links(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/trends/financial-overview/goal-account-links', [
            'links' => [],
        ])->assertOk();
    }

    public function test_endpoint_rejects_scalar_account_id(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/trends/financial-overview/goal-account-links', [
            'links' => ['fund-1' => 42],
        ])->assertStatus(422);
    }

    public function test_endpoint_rejects_non_integer_account_id(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/trends/financial-overview/goal-account-links', [
            'links' => ['fund-1' => ['not-an-int']],
        ])->assertStatus(422);
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->postJson('/trends/financial-overview/goal-account-links', [
            'links' => [],
        ])->assertStatus(401);
    }
}

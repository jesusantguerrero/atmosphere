<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TodayAttentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_underfunded_target_includes_structured_fields(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $group = Category::create([
            'team_id' => $team->id,
            'name' => 'Bills',
            'depth' => 0,
            'index' => 0,
            'resource_type' => 'transactions',
        ]);

        $category = Category::create([
            'team_id' => $team->id,
            'name' => 'House Rent',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
            'resource_type' => 'transactions',
        ]);

        BudgetMonth::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'month' => now()->startOfMonth()->format('Y-m-d'),
            'category_id' => $category->id,
            'name' => 'House Rent',
            'budgeted' => 0,
            'activity' => 0,
        ]);

        // Target due today (or in the past few days) with 0 funded → underfunded
        BudgetTarget::create([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'target_type' => 'spending',
            'amount' => 24000,
            'frequency' => 'monthly',
            'frequency_month_date' => now()->day,
            'completed_at' => null,
        ]);

        $response = $this->getJson('/api/mobile/today')
            ->assertOk();

        $attention = collect($response->json('attention'));
        $target = $attention->firstWhere('type', 'target_underfunded');

        $this->assertNotNull($target, 'Expected an underfunded target alert');
        $this->assertEquals('House Rent', $target['name']);
        $this->assertEquals(24000.0, $target['amount_needed']);
        $this->assertEquals('DOP', $target['currency_code']);
        $this->assertArrayHasKey('days_overdue', $target);
        $this->assertIsInt($target['days_overdue']);
        $this->assertEquals($target['category_id'], $category->id);
        $this->assertNotNull($target['due_date']);
        // Backwards-compatible: message still present
        $this->assertStringContainsString('House Rent', $target['message']);
    }

    public function test_funded_target_does_not_appear_in_attention(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $group = Category::create([
            'team_id' => $team->id,
            'name' => 'Bills',
            'depth' => 0,
            'index' => 0,
            'resource_type' => 'transactions',
        ]);

        $category = Category::create([
            'team_id' => $team->id,
            'name' => 'Fully Funded',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
            'resource_type' => 'transactions',
        ]);

        BudgetMonth::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'month' => now()->startOfMonth()->format('Y-m-d'),
            'category_id' => $category->id,
            'name' => 'Fully Funded',
            'budgeted' => 10000,
            'activity' => 0,
        ]);

        BudgetTarget::create([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'target_type' => 'spending',
            'amount' => 10000,
            'frequency' => 'monthly',
            'frequency_month_date' => now()->day,
            'completed_at' => null,
        ]);

        $response = $this->getJson('/api/mobile/today')
            ->assertOk();

        $attention = collect($response->json('attention'));
        $target = $attention->firstWhere('type', 'target_underfunded');

        $this->assertNull($target, 'Fully funded target should not appear');
    }

    public function test_money_includes_currency_code(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        Setting::updateOrCreate(
            ['team_id' => $team->id, 'name' => 'team_primary_currency_code'],
            ['user_id' => $user->id, 'value' => 'DOP'],
        );

        $response = $this->getJson('/api/mobile/today')
            ->assertOk();

        $this->assertEquals('DOP', $response->json('money.currency_code'));
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        return [$user->fresh(), $team];
    }
}

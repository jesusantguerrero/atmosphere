<?php

namespace Tests\Feature\Mobile;

use App\Domains\Transaction\Models\Transaction;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TodayEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_returns_expected_structure(): void
    {
        [$user] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/today')
            ->assertOk();

        $data = $response->json();
        $this->assertArrayHasKey('money', $data);
        $this->assertArrayHasKey('attention', $data);
        $this->assertArrayHasKey('today', $data);
        $this->assertArrayHasKey('upcoming', $data);
        $this->assertArrayHasKey('meal', $data);
        $this->assertArrayHasKey('pending_review_count', $data);
    }

    public function test_today_requires_authentication(): void
    {
        $this->getJson('/api/mobile/today')
            ->assertUnauthorized();
    }

    public function test_pending_review_count_reflects_draft_transactions(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->assertEquals(0, $this->getJson('/api/mobile/today')->json('pending_review_count'));

        Transaction::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'description' => 'Draft one',
            'total' => 100,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => 'draft',
        ]);

        Transaction::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'description' => 'Draft two',
            'total' => 200,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => 'draft',
        ]);

        $this->assertEquals(2, $this->getJson('/api/mobile/today')->json('pending_review_count'));
    }

    public function test_today_money_includes_currency(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        Setting::updateOrCreate(
            ['team_id' => $team->id, 'name' => 'team_primary_currency_code'],
            ['user_id' => $user->id, 'value' => 'DOP'],
        );

        $response = $this->getJson('/api/mobile/today')->assertOk();

        $this->assertArrayHasKey('currency_code', $response->json('money'));
    }

    public function test_today_attention_is_array(): void
    {
        [$user] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/today')->assertOk();

        $this->assertIsArray($response->json('attention'));
    }

    public function test_other_teams_drafts_are_not_counted(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();

        Transaction::create([
            'team_id' => $otherTeam->id,
            'user_id' => $other->id,
            'date' => now()->format('Y-m-d'),
            'description' => 'Other draft',
            'total' => 500,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => 'draft',
        ]);

        $this->assertEquals(0, $this->getJson('/api/mobile/today')->json('pending_review_count'));
    }

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        return [$user->fresh(), $team];
    }
}

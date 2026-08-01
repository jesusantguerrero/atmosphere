<?php

namespace Tests\Feature\LogerProfile;

use App\Domains\AppCore\Models\Category;
use App\Domains\AppCore\Models\CoreModule;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Models\LogerProfileEntity;
use App\Domains\LogerProfile\Services\LogerProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A profile with no usable category entities used to blow up with
 * "Undefined array key 0" while reading the team off the first entity.
 */
class ProfileTransactionsTest extends TestCase
{
    use RefreshDatabase;

    private function profileFor(User $user, int $teamId): LogerProfile
    {
        return LogerProfile::create([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'name' => 'partner',
        ]);
    }

    public function test_profile_without_entities_returns_an_empty_report(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $profile = $this->profileFor($user, $team->id);

        $report = app(LogerProfileService::class)
            ->getTransactionsByProfileId($profile->id, '2026-08-01', '2026-08-31');

        $this->assertCount(0, $report['data']);
        $this->assertSame(0, $report['total']);
    }

    public function test_profile_whose_categories_were_deleted_returns_an_empty_report(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $profile = $this->profileFor($user, $team->id);

        $category = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Gifts',
            'resource_type' => 'transactions',
        ]);

        LogerProfileEntity::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'profile_id' => $profile->id,
            'name' => $category->name,
            'entity_type' => Category::class,
            'entity_id' => $category->id,
        ]);

        $category->delete();

        $report = app(LogerProfileService::class)
            ->getTransactionsByProfileId($profile->id, '2026-08-01', '2026-08-31');

        $this->assertCount(0, $report['data']);
        $this->assertSame(0, $report['total']);
    }

    public function test_transactions_endpoint_responds_for_a_profile_without_entities(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $profile = $this->profileFor($user, $team->id);

        CoreModule::updateOrCreate([
            'team_id' => $team->id,
            'name' => 'profiles',
        ], [
            'user_id' => $user->id,
            'enabled' => true,
        ]);

        $this->actingAs($user)
            ->get("/loger-profiles/{$profile->id}/transactions?filter[date]=2026-08-01~2026-08-31")
            ->assertOk()
            ->assertJson(['total' => 0]);
    }
}

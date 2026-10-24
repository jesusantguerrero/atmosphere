<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BudgetUncategorizedTeamScopeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function uncategorized_summary_includes_other_team_members_transactions(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->ownedTeams()->first();
        $owner->forceFill(['current_team_id' => $team->id])->save();

        $member = User::factory()->create();
        $member->teams()->attach($team, ['role' => 'editor']);
        $member->forceFill(['current_team_id' => $team->id])->save();

        $account = Account::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'name' => 'Checking',
            'currency_code' => 'DOP',
            'display_id' => 'bank',
            'account_detail_type_id' => 1,
        ]);

        Transaction::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'account_id' => $account->id,
            'date' => now()->format('Y-m-d'),
            'total' => 500,
            'direction' => Transaction::DIRECTION_CREDIT,
            'currency_code' => 'DOP',
            'description' => 'Uncategorized from member',
            'status' => Transaction::STATUS_VERIFIED,
            'category_id' => null,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/mobile/budget?month='.now()->format('Y-m'))
            ->assertOk();

        $uncategorized = $response->json('summary.uncategorized');
        $this->assertNotEmpty($uncategorized, 'Uncategorized should include transactions from other team members');

        $dop = collect($uncategorized)->firstWhere('currency_code', 'DOP');
        $this->assertNotNull($dop);
        $this->assertEquals(500, $dop['total']);
        $this->assertEquals(1, $dop['count']);
    }

    #[Test]
    public function uncategorized_summary_excludes_other_teams(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->ownedTeams()->first();

        $otherAccount = Account::create([
            'team_id' => $otherTeam->id,
            'user_id' => $otherUser->id,
            'name' => 'Other Bank',
            'currency_code' => 'DOP',
            'display_id' => 'bank',
            'account_detail_type_id' => 1,
        ]);

        Transaction::create([
            'team_id' => $otherTeam->id,
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'date' => now()->format('Y-m-d'),
            'total' => 999,
            'direction' => Transaction::DIRECTION_CREDIT,
            'currency_code' => 'DOP',
            'description' => 'Other team uncategorized',
            'status' => Transaction::STATUS_VERIFIED,
            'category_id' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/budget?month='.now()->format('Y-m'))
            ->assertOk();

        $uncategorized = $response->json('summary.uncategorized');
        $dop = collect($uncategorized)->firstWhere('currency_code', 'DOP');
        $this->assertNull($dop, 'Should not see uncategorized from a different team');
    }
}

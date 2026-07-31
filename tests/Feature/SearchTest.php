<?php

namespace Tests\Feature;

use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insane\Journal\Models\Core\Payee;
use Tests\TestCase;

/**
 * The header command palette hits `/search` on every keystroke. These pin the
 * two things that make it safe to expose globally: it never crosses team
 * boundaries, and it never scans on a term too short to be meaningful.
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        return [$user, $team];
    }

    private function makeTransaction(int $teamId, int $userId, string $description, ?int $payeeId = null): Transaction
    {
        return Transaction::forceCreate([
            'team_id' => $teamId,
            'user_id' => $userId,
            'payee_id' => $payeeId,
            'status' => Transaction::STATUS_VERIFIED,
            'direction' => Transaction::DIRECTION_CREDIT,
            'date' => now()->format('Y-m-d'),
            'total' => 1200,
            'description' => $description,
            'currency_code' => 'DOP',
        ]);
    }

    public function test_it_finds_transactions_by_description(): void
    {
        [$user, $team] = $this->makeUser();
        $this->makeTransaction($team->id, $user->id, 'Supermercado Nacional');
        $this->makeTransaction($team->id, $user->id, 'Netflix subscription');

        $response = $this->actingAs($user)->getJson('/search?search=Nacional');

        $response->assertOk();
        $this->assertSame(['transactions'], array_keys($response->json()));
        $response->assertJsonCount(1, 'transactions');
        $response->assertJsonPath('transactions.0.title', 'Supermercado Nacional');
        $response->assertJsonPath('transactions.0.type', 'transactions');
    }

    public function test_it_finds_transactions_through_their_payee_name(): void
    {
        [$user, $team] = $this->makeUser();
        $payee = Payee::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Claro']);
        $this->makeTransaction($team->id, $user->id, 'Monthly bill', $payee->id);

        $response = $this->actingAs($user)->getJson('/search?search=Claro');

        $response->assertOk();
        $response->assertJsonPath('transactions.0.title', 'Monthly bill');
        $response->assertJsonPath('transactions.0.subtitle', 'Claro');
        $response->assertJsonPath('payees.0.title', 'Claro');
    }

    public function test_it_never_returns_another_teams_data(): void
    {
        [$user, $team] = $this->makeUser();
        [$otherUser, $otherTeam] = $this->makeUser();

        $this->makeTransaction($team->id, $user->id, 'Shared keyword mine');
        $this->makeTransaction($otherTeam->id, $otherUser->id, 'Shared keyword theirs');
        Payee::create(['team_id' => $otherTeam->id, 'user_id' => $otherUser->id, 'name' => 'Shared keyword payee']);

        $response = $this->actingAs($user)->getJson('/search?search=Shared keyword');

        $response->assertOk();
        $response->assertJsonCount(1, 'transactions');
        $response->assertJsonPath('transactions.0.title', 'Shared keyword mine');
        $response->assertJsonMissing(['title' => 'Shared keyword payee']);
    }

    public function test_it_ignores_terms_shorter_than_three_characters(): void
    {
        [$user, $team] = $this->makeUser();
        $this->makeTransaction($team->id, $user->id, 'Uber ride');

        $response = $this->actingAs($user)->getJson('/search?search=Ub');

        $response->assertOk();
        $this->assertSame([], $response->json());
    }

    public function test_it_omits_groups_with_no_matches(): void
    {
        [$user, $team] = $this->makeUser();
        Payee::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Farmacia Carol']);

        $response = $this->actingAs($user)->getJson('/search?search=Farmacia');

        $response->assertOk();
        $this->assertSame(['payees'], array_keys($response->json()));
    }

    public function test_it_treats_wildcards_as_literal_text(): void
    {
        [$user, $team] = $this->makeUser();
        $this->makeTransaction($team->id, $user->id, 'Regular purchase');

        $response = $this->actingAs($user)->getJson('/search?search=%%%');

        $response->assertOk();
        $this->assertSame([], $response->json());
    }

    public function test_it_excludes_drafts_and_soft_deleted_transactions(): void
    {
        [$user, $team] = $this->makeUser();
        $draft = $this->makeTransaction($team->id, $user->id, 'Pending groceries');
        $draft->forceFill(['status' => 'draft'])->save();

        $deleted = $this->makeTransaction($team->id, $user->id, 'Deleted groceries');
        $deleted->delete();

        $response = $this->actingAs($user)->getJson('/search?search=groceries');

        $response->assertOk();
        $this->assertSame([], $response->json());
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/search?search=anything')->assertUnauthorized();
    }
}

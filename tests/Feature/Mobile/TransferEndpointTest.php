<?php

namespace Tests\Feature\Mobile;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Models\Core\AccountDetailType;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransferEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(AccountDetailTypesCreate::class)->create();
    }

    public function test_transfer_between_own_accounts(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $from = $this->createAccount($team, $user, 'Checking');
        $to = $this->createAccount($team, $user, 'Savings', 'savings');

        $response = $this->postJson('/api/mobile/transfers', [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'total' => 5000,
            'date' => '2026-10-24',
        ])->assertCreated()->assertJson(['success' => true]);

        $this->assertEquals(5000.0, $response->json('data.total'));
        $this->assertNotNull($response->json('data.id'));
    }

    public function test_transfer_rejects_same_account(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $account = $this->createAccount($team, $user, 'Checking');

        $this->postJson('/api/mobile/transfers', [
            'from_account_id' => $account->id,
            'to_account_id' => $account->id,
            'total' => 1000,
            'date' => '2026-10-24',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['to_account_id']);
    }

    public function test_transfer_rejects_other_teams_account(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $from = $this->createAccount($team, $user, 'Checking');

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $alien = $this->createAccount($otherTeam, $other, 'Foreign');

        $this->postJson('/api/mobile/transfers', [
            'from_account_id' => $from->id,
            'to_account_id' => $alien->id,
            'total' => 1000,
            'date' => '2026-10-24',
        ])->assertNotFound();
    }

    public function test_transfer_validates_required_fields(): void
    {
        [$user] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/transfers', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['from_account_id', 'to_account_id', 'total', 'date']);
    }

    public function test_transfer_validates_minimum_amount(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $from = $this->createAccount($team, $user, 'Checking');
        $to = $this->createAccount($team, $user, 'Savings', 'savings');

        $this->postJson('/api/mobile/transfers', [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'total' => 0,
            'date' => '2026-10-24',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['total']);
    }

    public function test_transfer_creates_journal_lines(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $from = $this->createAccount($team, $user, 'Checking');
        $to = $this->createAccount($team, $user, 'Savings', 'savings');

        $response = $this->postJson('/api/mobile/transfers', [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'total' => 2000,
            'date' => '2026-10-24',
        ])->assertCreated();

        $txId = $response->json('data.id');
        $tx = Transaction::find($txId);

        $this->assertTrue($tx->lines()->exists(), 'Transfer must create journal lines');
    }

    public function test_transfer_fires_transaction_created_event(): void
    {
        Event::fake([TransactionCreated::class]);

        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $from = $this->createAccount($team, $user, 'Checking');
        $to = $this->createAccount($team, $user, 'Savings', 'savings');

        $this->postJson('/api/mobile/transfers', [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'total' => 3000,
            'date' => '2026-10-24',
        ])->assertCreated();

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_transfer_sets_description_automatically(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $from = $this->createAccount($team, $user, 'BHD Checking');
        $to = $this->createAccount($team, $user, 'Popular Savings', 'savings');

        $response = $this->postJson('/api/mobile/transfers', [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'total' => 1500,
            'date' => '2026-10-24',
        ])->assertCreated();

        $this->assertStringContainsString('BHD Checking', $response->json('data.description'));
        $this->assertStringContainsString('Popular Savings', $response->json('data.description'));
    }

    public function test_transfer_accepts_custom_description(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $from = $this->createAccount($team, $user, 'Checking');
        $to = $this->createAccount($team, $user, 'Savings', 'savings');

        $response = $this->postJson('/api/mobile/transfers', [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'total' => 800,
            'date' => '2026-10-24',
            'description' => 'Monthly savings deposit',
        ])->assertCreated();

        $this->assertEquals('Monthly savings deposit', $response->json('data.description'));
    }

    public function test_transfer_requires_authentication(): void
    {
        $this->postJson('/api/mobile/transfers', [
            'from_account_id' => 1,
            'to_account_id' => 2,
            'total' => 1000,
            'date' => '2026-10-24',
        ])->assertUnauthorized();
    }

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        return [$user->fresh(), $team];
    }

    private function createAccount($team, User $user, string $name, string $detailTypeName = 'bank'): Account
    {
        $type = AccountDetailType::where('name', $detailTypeName)->first();

        return Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => $name,
            'display_id' => Str::slug($name),
            'currency_code' => 'DOP',
            'account_detail_type_id' => $type->id,
            'balance_type' => 'DEBIT',
            'type' => 1,
        ]);
    }
}

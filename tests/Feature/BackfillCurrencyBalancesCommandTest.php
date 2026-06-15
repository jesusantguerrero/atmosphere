<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CurrencyBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insane\Journal\Models\Core\Transaction;
use Tests\TestCase;

class BackfillCurrencyBalancesCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
    }

    /** @test */
    public function it_skips_non_multi_currency_accounts(): void
    {
        Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'is_multi_currency' => false,
            'currency_code' => 'DOP',
        ]);

        $this->artisan('multicurrency:backfill-balances')
            ->expectsOutput('No multi-currency accounts found.')
            ->assertSuccessful();

        $this->assertDatabaseCount('currency_balances', 0);
    }

    /** @test */
    public function it_writes_zero_when_no_transactions_exist_for_secondary_currency(): void
    {
        $account = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'is_multi_currency' => true,
            'currency_code' => 'DOP',
            'secondary_currencies' => ['USD'],
        ]);

        $this->artisan('multicurrency:backfill-balances')->assertSuccessful();

        $row = CurrencyBalance::where('account_id', $account->id)
            ->where('currency_code', 'USD')
            ->first();

        $this->assertNotNull($row);
        $this->assertEquals(0.0, (float) $row->pending_balance);
    }

    /** @test */
    public function dry_run_does_not_write_to_currency_balances(): void
    {
        Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'is_multi_currency' => true,
            'currency_code' => 'DOP',
            'secondary_currencies' => ['USD'],
        ]);

        $this->artisan('multicurrency:backfill-balances', ['--dry' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('currency_balances', 0);
    }

    /** @test */
    public function it_is_idempotent(): void
    {
        Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'is_multi_currency' => true,
            'currency_code' => 'DOP',
            'secondary_currencies' => ['USD'],
        ]);

        $this->artisan('multicurrency:backfill-balances')->assertSuccessful();
        $this->artisan('multicurrency:backfill-balances')->assertSuccessful();

        $this->assertDatabaseCount('currency_balances', 1);
    }

    /** @test */
    public function team_filter_scopes_to_one_team(): void
    {
        $otherUser = User::factory()->withPersonalTeam()->create();

        Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'is_multi_currency' => true,
            'currency_code' => 'DOP',
            'secondary_currencies' => ['USD'],
        ]);

        Account::factory()->create([
            'team_id' => $otherUser->current_team_id,
            'user_id' => $otherUser->id,
            'is_multi_currency' => true,
            'currency_code' => 'DOP',
            'secondary_currencies' => ['USD'],
        ]);

        $this->artisan('multicurrency:backfill-balances', [
            '--team' => $this->user->current_team_id,
        ])->assertSuccessful();

        $this->assertDatabaseCount('currency_balances', 1);
    }
}

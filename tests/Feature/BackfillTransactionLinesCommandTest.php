<?php

namespace Tests\Feature;

use App\Domains\Transaction\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BackfillTransactionLinesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_reports_until_it_is_told_to_apply(): void
    {
        [, $team, $bank] = $this->makeTeam();
        $expense = $this->lineless($team, $bank, 100);

        $this->artisan('transactions:backfill-lines')
            ->expectsOutputToContain('Would write lines for 1 transactions')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('transaction_lines')->where('transaction_id', $expense->id)->count());
    }

    public function test_it_gives_expenses_and_transfers_their_lines_and_the_balances_follow(): void
    {
        [, $team, $bank, $savings] = $this->makeTeam();
        $expense = $this->lineless($team, $bank, 100);
        $transfer = $this->lineless($team, $bank, 40, ['counter_account_id' => $savings->id, 'description' => 'Transfer']);

        $this->artisan('transactions:backfill-lines', ['--apply' => true, '--force' => true, '--no-rollover' => true])
            ->expectsOutputToContain('Wrote lines for 2 transactions')
            ->assertExitCode(0);

        $this->assertSame(2, DB::table('transaction_lines')->where('transaction_id', $expense->id)->count());
        $this->assertSame(2, DB::table('transaction_lines')->where('transaction_id', $transfer->id)->count());
        $this->assertEquals(-140, $this->balance($bank)); // 100 spent + 40 sent
        $this->assertEquals(40, $this->balance($savings)); // the transfer arrived
    }

    public function test_it_is_safe_to_run_again_and_never_touches_what_has_lines(): void
    {
        [$user, $team, $bank, , $groceries] = $this->makeTeam();
        $lineless = $this->lineless($team, $bank, 100);

        // One made the normal way, with its lines.
        Sanctum::actingAs($user);
        $withLines = $this->postJson('/api/mobile/transactions', ['account_id' => $bank->id, 'total' => 75, 'currency_code' => 'DOP', 'description' => 'Fine', 'date' => '2026-10-10', 'direction' => 'WITHDRAW', 'category_id' => $groceries->id])->assertStatus(201)->json('data.id');
        $linesBefore = DB::table('transaction_lines')->where('transaction_id', $withLines)->pluck('id')->all();

        $options = ['--apply' => true, '--force' => true, '--no-rollover' => true];
        $this->artisan('transactions:backfill-lines', $options)->expectsOutputToContain('Wrote lines for 1 transactions')->assertExitCode(0);
        $this->artisan('transactions:backfill-lines', $options)->expectsOutputToContain('Nothing to backfill')->assertExitCode(0);

        $this->assertSame(2, DB::table('transaction_lines')->where('transaction_id', $lineless->id)->count());
        $this->assertSame($linesBefore, DB::table('transaction_lines')->where('transaction_id', $withLines)->pluck('id')->all());
        $this->assertEquals(-175, $this->balance($bank));
    }

    public function test_it_leaves_alone_what_it_should_not_guess_and_says_so(): void
    {
        [, $team, $bank] = $this->makeTeam();
        $planned = $this->lineless($team, $bank, 10, ['status' => 'planned']);
        $multi = $this->lineless($team, $bank, 20, ['exchange_rate' => 58.5, 'exchange_amount' => 1170]);
        $usd = $this->lineless($team, $bank, 30, ['currency_code' => 'USD']);
        $flagged = $this->lineless($team, $bank, 50, ['is_transfer' => true]);

        $this->artisan('transactions:backfill-lines', ['--apply' => true, '--force' => true, '--no-rollover' => true])
            ->expectsOutputToContain('Nothing to backfill')
            ->assertExitCode(0);

        foreach ([$planned, $multi, $usd, $flagged] as $skipped) {
            $this->assertSame(0, DB::table('transaction_lines')->where('transaction_id', $skipped->id)->count());
        }
    }

    public function test_it_can_be_limited_to_one_team_and_to_dates(): void
    {
        [, $team, $bank] = $this->makeTeam();
        [, $otherTeam, $otherBank] = $this->makeTeam();
        $mine = $this->lineless($team, $bank, 100, ['date' => '2026-10-10']);
        $old = $this->lineless($team, $bank, 100, ['date' => '2026-01-05']);
        $theirs = $this->lineless($otherTeam, $otherBank, 100);

        $this->artisan('transactions:backfill-lines', ['--apply' => true, '--force' => true, '--no-rollover' => true, '--team' => $team->id, '--from' => '2026-06-01'])->assertExitCode(0);

        $this->assertSame(2, DB::table('transaction_lines')->where('transaction_id', $mine->id)->count());
        $this->assertSame(0, DB::table('transaction_lines')->where('transaction_id', $old->id)->count());
        $this->assertSame(0, DB::table('transaction_lines')->where('transaction_id', $theirs->id)->count());

        $this->artisan('transactions:backfill-lines', ['--from' => 'yesterday'])->assertExitCode(2); // not a day
    }

    public function test_it_rolls_the_budget_forward_after_writing(): void
    {
        [, $team, $bank] = $this->makeTeam();
        $this->lineless($team, $bank, 100, ['date' => now()->toDateString()]);

        $this->artisan('transactions:backfill-lines', ['--apply' => true, '--force' => true])
            ->expectsOutputToContain('budget rolled forward')
            ->assertExitCode(0);
    }

    public function test_a_transfer_from_the_app_now_moves_money_on_both_accounts(): void
    {
        [$user, $team, $bank, $savings] = $this->makeTeam();
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/mobile/transfers', ['from_account_id' => $bank->id, 'to_account_id' => $savings->id, 'total' => 250, 'date' => '2026-10-10'])->assertStatus(201)->json('data.id');

        $this->assertSame(2, DB::table('transaction_lines')->where('transaction_id', $id)->count());
        $this->assertEquals(-250, $this->balance($bank));
        $this->assertEquals(250, $this->balance($savings));
    }

    /** @return array{0: User, 1: object, 2: Account, 3: Account, 4: \App\Domains\AppCore\Models\Category} */
    private function makeTeam(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $account = fn (string $name) => Account::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => $name, 'display_id' => strtolower($name).$team->id, 'currency_code' => 'DOP', 'balance_type' => 'DEBIT', 'type' => 1]);
        $group = \App\Domains\AppCore\Models\Category::create(['name' => 'Expenses', 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 99]);
        $groceries = \App\Domains\AppCore\Models\Category::create(['name' => 'Groceries', 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => $group->id, 'depth' => 1, 'index' => 0]);

        return [$user->fresh(), $team, $account('Bank'), $account('Savings'), $groceries];
    }

    /** A transaction saved the way the app used to: the row, and no lines. */
    private function lineless($team, Account $account, float $total, array $extra = []): Transaction
    {
        return Transaction::create([
            'team_id' => $team->id,
            'user_id' => $account->user_id,
            'account_id' => $account->id,
            'total' => $total,
            'currency_code' => 'DOP',
            'description' => 'Sirena',
            'date' => '2026-10-10',
            'direction' => 'WITHDRAW',
            'status' => 'verified',
            ...$extra,
        ]);
    }

    private function balance(Account $account): float
    {
        return (float) DB::table('transaction_lines')->where('account_id', $account->id)->sum(DB::raw('amount * type'));
    }
}

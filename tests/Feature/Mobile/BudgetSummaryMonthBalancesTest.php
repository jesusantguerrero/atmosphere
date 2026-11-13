<?php

namespace Tests\Feature\Mobile;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\AccountDetailType;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The mobile budget summary compared a past month's budget against today's
 * account balances (October showed a "Diferencia por revisar" that only
 * existed because of November's movements). Past months use the balances
 * they closed with, like the web banner.
 */
class BudgetSummaryMonthBalancesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();
        Carbon::setTestNow('2026-11-13');
    }

    private function deposit(User $user, Account $bank, string $date, float $amount): void
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'account_id' => $bank->id,
            'date' => $date,
            'description' => 'Salary',
            'direction' => 'DEPOSIT',
            'total' => $amount,
            'currency_code' => 'DOP',
            'status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transaction_lines')->insert([
            'transaction_id' => $transactionId,
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'account_id' => $bank->id,
            'date' => $date,
            'type' => 1,
            'amount' => $amount,
            'anchor' => 1,
            'index' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_past_month_summary_uses_the_balances_at_its_close(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();
        $bank = Account::create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => 'Checking',
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::BANK)->value('id'),
        ]);
        $this->deposit($user, $bank, '2026-08-10', 1000);
        $this->deposit($user, $bank, '2026-11-05', 500);
        Sanctum::actingAs($user);

        $september = collect($this->getJson('/api/mobile/budget?month=2026-09')->assertOk()->json('summary.rows'))->firstWhere('currency_code', 'DOP');
        $november = collect($this->getJson('/api/mobile/budget?month=2026-11')->assertOk()->json('summary.rows'))->firstWhere('currency_code', 'DOP');

        $this->assertEquals(1000, $september['cash_in_accounts'], 'September closed with only the August deposit');
        $this->assertEquals(1500, $november['cash_in_accounts'], 'the current month uses live balances');
    }
}

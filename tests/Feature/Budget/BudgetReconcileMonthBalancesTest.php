<?php

namespace Tests\Feature\Budget;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\AccountDetailType;
use Tests\TestCase;

/**
 * The reconcile banner compared a past month's budget against today's account
 * balances. A past month needs the balances as they were when it closed.
 */
class BudgetReconcileMonthBalancesTest extends TestCase
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

    public function test_a_past_month_reconciles_against_balances_at_its_close(): void
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
        $this->deposit($user, $bank, '2026-10-05', 500);

        $this->actingAs($user)
            ->get('/budgets?filter[date]=2026-09-01~2026-09-30')
            ->assertInertia(fn ($page) => $page
                ->where("accountBalances.{$bank->id}", fn ($balance) => (float) $balance === 1000.0)
            );

        $this->get('/budgets?filter[date]=2026-11-01~2026-11-30')
            ->assertInertia(fn ($page) => $page->where('accountBalances', null));
    }
}

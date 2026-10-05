<?php

namespace Tests\Feature\Budget;

use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Budget\Services\BudgetRolloverService;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Models\Account;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Category;
use Tests\TestCase;

/**
 * `startFrom` used to roll every month from the touched one up to today.
 * The only thing a month inherits from the previous one is its carry, so
 * once a month's carry comes out unchanged the months in between are
 * skipped; the current month is always rolled because it may never have
 * been opened.
 */
class BudgetRolloverShortCircuitTest extends TestCase
{
    use RefreshDatabase;

    private const NOW = '2026-09-01';

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();
        Carbon::setTestNow(self::NOW);
    }

    /**
     * @return array{0: Team, 1: User, 2: Account, 3: Category}
     */
    private function teamWithBankAndCategory(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $bank = Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Bank Checking',
            'currency_code' => 'USD',
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::BANK)->value('id'),
        ]);

        $category = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();

        BudgetMonth::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'month' => '2026-06-01',
            'name' => '2026-06-01',
            'budgeted' => 50,
        ]);

        return [$team, $user, $bank, $category];
    }

    private function recordOutflow(Team $team, User $user, Account $bank, Category $category, string $date, float $amount): void
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $bank->id,
            'category_id' => $category->id,
            'date' => $date,
            'description' => 'Fee',
            'direction' => 'WITHDRAW',
            'total' => $amount,
            'currency_code' => 'USD',
            'status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transaction_lines')->insert([
            'transaction_id' => $transactionId,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $bank->id,
            'category_id' => $category->id,
            'date' => $date,
            'type' => -1,
            'amount' => $amount,
            'anchor' => 1,
            'index' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * A real service that records every month it rolls in `$rolled`.
     */
    private function recordingService(): BudgetRolloverService
    {
        return new class(new BudgetCategoryService) extends BudgetRolloverService
        {
            /** @var list<string> */
            public array $rolled = [];

            public function rollMonth($teamId, $month, $categories = null): bool
            {
                $this->rolled[] = $month;

                return parent::rollMonth($teamId, $month, $categories);
            }
        };
    }

    private function leftInMonth(Team $team, Category $category, string $month): float
    {
        return (float) BudgetMonth::where([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'month' => $month,
        ])->value('left_from_last_month');
    }

    public function test_first_roll_cascades_through_every_month_up_to_today(): void
    {
        [$team, $user, $bank, $category] = $this->teamWithBankAndCategory();
        $this->recordOutflow($team, $user, $bank, $category, '2026-06-15', 20);

        $service = $this->recordingService();
        $service->startFrom($team->id, '2026-06');

        $this->assertSame(['2026-06-01', '2026-07-01', '2026-08-01', '2026-09-01'], $service->rolled);
        $this->assertSame(30.0, $this->leftInMonth($team, $category, '2026-07-01'), 'budgeted 50 - outflow 20');
        $this->assertSame(30.0, $this->leftInMonth($team, $category, '2026-09-01'), 'carried untouched through July and August');
    }

    public function test_reroll_with_unchanged_carry_skips_the_months_in_between(): void
    {
        [$team, $user, $bank, $category] = $this->teamWithBankAndCategory();
        $this->recordOutflow($team, $user, $bank, $category, '2026-06-15', 20);
        app(BudgetRolloverService::class)->startFrom($team->id, '2026-06');

        $service = $this->recordingService();
        $service->startFrom($team->id, '2026-06');

        $this->assertSame(['2026-06-01', '2026-09-01'], $service->rolled, 'June settles; only the current month is still rolled');
        $this->assertSame(30.0, $this->leftInMonth($team, $category, '2026-09-01'));
    }

    public function test_changed_carry_keeps_cascading_until_it_settles(): void
    {
        [$team, $user, $bank, $category] = $this->teamWithBankAndCategory();
        $this->recordOutflow($team, $user, $bank, $category, '2026-06-15', 20);
        app(BudgetRolloverService::class)->startFrom($team->id, '2026-06');

        $this->recordOutflow($team, $user, $bank, $category, '2026-06-20', 5);
        $service = $this->recordingService();
        $service->startFrom($team->id, '2026-06');

        $this->assertSame(['2026-06-01', '2026-07-01', '2026-08-01', '2026-09-01'], $service->rolled);
        $this->assertSame(25.0, $this->leftInMonth($team, $category, '2026-09-01'), 'the extra 5 outflow reached September');
    }

    public function test_ready_to_assign_carry_is_part_of_the_settle_check(): void
    {
        [$team, $user, $bank, $category] = $this->teamWithBankAndCategory();
        $this->recordOutflow($team, $user, $bank, $category, '2026-06-15', 20);
        app(BudgetRolloverService::class)->startFrom($team->id, '2026-06');

        $readyToAssign = Category::where(['team_id' => $team->id, 'name' => BudgetReservedNames::READY_TO_ASSIGN->value])->firstOrFail();
        BudgetMonth::where(['team_id' => $team->id, 'category_id' => $readyToAssign->id, 'month' => '2026-07-01'])
            ->update(['left_from_last_month' => 999]);

        $service = $this->recordingService();
        $service->startFrom($team->id, '2026-06');

        $this->assertSame(['2026-06-01', '2026-07-01', '2026-09-01'], $service->rolled, 'June sees the drifted Ready to Assign carry and re-rolls July; July then settles');
        $this->assertNotSame(999.0, $this->leftInMonth($team, $readyToAssign, '2026-07-01'));
    }
}

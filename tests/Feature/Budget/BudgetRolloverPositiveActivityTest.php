<?php

namespace Tests\Feature\Budget;

use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Services\BudgetRolloverService;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Models\Account;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Category;
use Tests\TestCase;

/**
 * The rollover used to compute `available = budgeted + left - |activity|`,
 * so an inflow categorized directly into a normal category (positive
 * activity) was subtracted instead of added. The error then compounded
 * through `left_from_last_month` every following month.
 */
class BudgetRolloverPositiveActivityTest extends TestCase
{
    use RefreshDatabase;

    private const MONTH = '2026-08-01';

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();
        Carbon::setTestNow(self::MONTH);
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
            'month' => self::MONTH,
            'name' => self::MONTH,
            'budgeted' => 50,
            'left_from_last_month' => 10,
        ]);

        return [$team, $user, $bank, $category];
    }

    private function recordLine(Team $team, User $user, Account $bank, Category $category, int $type, float $amount): void
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $bank->id,
            'category_id' => $category->id,
            'date' => self::MONTH,
            'description' => $type > 0 ? 'Interest earned' : 'Fee',
            'direction' => $type > 0 ? 'DEPOSIT' : 'WITHDRAW',
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
            'date' => self::MONTH,
            'type' => $type,
            'amount' => $amount,
            'anchor' => 1,
            'index' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function availableFor(Team $team, Category $category): float
    {
        return (float) BudgetMonth::where([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'month' => self::MONTH,
        ])->value('available');
    }

    public function test_positive_activity_is_added_to_available(): void
    {
        [$team, $user, $bank, $category] = $this->teamWithBankAndCategory();
        $this->recordLine($team, $user, $bank, $category, 1, 20);

        app(BudgetRolloverService::class)->startFrom($team->id, '2026-08');

        $this->assertSame(80.0, $this->availableFor($team, $category), 'budgeted 50 + left 10 + inflow 20');
    }

    public function test_rounds_available_to_the_team_currency_scale(): void
    {
        [$team, $user, $bank, $category] = $this->teamWithBankAndCategory();
        Setting::updateOrCreate(
            ['team_id' => $team->id, 'name' => 'team_primary_currency_code'],
            ['user_id' => $user->id, 'value' => 'JPY']
        );
        BudgetMonth::where(['team_id' => $team->id, 'category_id' => $category->id, 'month' => self::MONTH])
            ->update(['budgeted' => 50.4]);
        $this->recordLine($team, $user, $bank, $category, 1, 20);

        app(BudgetRolloverService::class)->startFrom($team->id, '2026-08');

        $this->assertSame(80.0, $this->availableFor($team, $category), 'JPY has no decimals: 50.4 + 10 + 20 rounds to 80, not 80.4');
    }

    public function test_negative_activity_is_still_subtracted_from_available(): void
    {
        [$team, $user, $bank, $category] = $this->teamWithBankAndCategory();
        $this->recordLine($team, $user, $bank, $category, -1, 20);

        app(BudgetRolloverService::class)->startFrom($team->id, '2026-08');

        $this->assertSame(40.0, $this->availableFor($team, $category), 'budgeted 50 + left 10 - outflow 20');
    }
}

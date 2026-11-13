<?php

namespace Tests\Feature\Budget;

use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
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
 * Paying a card past its debt leaves the card in credit and its payment
 * category below zero. The rollover used to reset that to 0 like an
 * overspent category, so spending the credit later filled the payment
 * category again and it ended up holding more than the card owed.
 */
class BudgetRolloverCardCreditTest extends TestCase
{
    use RefreshDatabase;

    private const PAID = '2026-07-01';

    private const SPENT = '2026-08-01';

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();
        Carbon::setTestNow(self::SPENT);
    }

    /**
     * @return array{0: Team, 1: User, 2: Account, 3: Category, 4: Category}
     */
    private function teamWithFundedCard(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $card = Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Visa',
            'currency_code' => 'USD',
            'credit_closing_day' => 15,
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::CREDIT_CARD)->value('id'),
        ]);
        // Creating a card account also creates its payment category.
        $paymentCategory = Category::where(['team_id' => $team->id, 'account_id' => $card->id])->whereNotNull('parent_id')->firstOrFail();
        $groceries = Category::where('team_id', $team->id)
            ->whereNotNull('parent_id')
            ->whereNull('account_id')
            ->where('name', '!=', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->firstOrFail();

        // The 1,000 owed on the card was already funded before July.
        BudgetMonth::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $paymentCategory->id,
            'month' => self::PAID,
            'name' => self::PAID,
            'left_from_last_month' => 1000,
        ]);
        BudgetMonth::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $groceries->id,
            'month' => self::SPENT,
            'name' => self::SPENT,
            'budgeted' => 500,
        ]);

        return [$team, $user, $card, $paymentCategory, $groceries];
    }

    private function cardLine(Team $team, User $user, Account $card, ?int $categoryId, int $type, float $amount, string $date): void
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $card->id,
            'category_id' => $categoryId,
            'date' => $date,
            'description' => $type > 0 ? 'Card payment' : 'Groceries',
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
            'account_id' => $card->id,
            'category_id' => $categoryId,
            'date' => $date,
            'type' => $type,
            'amount' => $amount,
            'anchor' => 1,
            'index' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function month(Team $team, Category $category, string $month): BudgetMonth
    {
        return BudgetMonth::where(['team_id' => $team->id, 'category_id' => $category->id, 'month' => $month])->firstOrFail();
    }

    public function test_spending_a_card_credit_keeps_the_payment_category_equal_to_the_debt(): void
    {
        [$team, $user, $card, $paymentCategory, $groceries] = $this->teamWithFundedCard();
        $this->cardLine($team, $user, $card, null, 1, 1500, self::PAID);
        $this->cardLine($team, $user, $card, $groceries->id, -1, 500, self::SPENT);

        app(BudgetRolloverService::class)->startFrom($team->id, '2026-07');

        $this->assertEqualsWithDelta(-500, (float) $this->month($team, $paymentCategory, self::PAID)->available, 0.001, 'paid 500 past the debt');
        $this->assertEqualsWithDelta(-500, (float) $this->month($team, $paymentCategory, self::SPENT)->left_from_last_month, 0.001, 'the credit carries into August');
        $this->assertEqualsWithDelta(0, (float) $this->month($team, $paymentCategory, self::SPENT)->available, 0.001, 'spending the credit leaves nothing owed');
    }

    public function test_a_card_in_credit_is_not_overspending_taken_from_ready_to_assign(): void
    {
        [$team, $user, $card] = $this->teamWithFundedCard();
        $this->cardLine($team, $user, $card, null, 1, 1500, self::PAID);

        app(BudgetRolloverService::class)->startFrom($team->id, '2026-07');

        $readyToAssign = Category::where(['team_id' => $team->id, 'name' => BudgetReservedNames::READY_TO_ASSIGN->value])->firstOrFail();
        $this->assertEqualsWithDelta(0, (float) $this->month($team, $readyToAssign, self::SPENT)->overspending_previous_month, 0.001);
    }

    public function test_an_overspent_spending_category_still_resets_to_zero(): void
    {
        [$team, $user, $card, , $groceries] = $this->teamWithFundedCard();
        $this->cardLine($team, $user, $card, $groceries->id, -1, 800, self::PAID);

        app(BudgetRolloverService::class)->startFrom($team->id, '2026-07');

        $this->assertEqualsWithDelta(0, (float) $this->month($team, $groceries, self::SPENT)->left_from_last_month, 0.001);
    }
}

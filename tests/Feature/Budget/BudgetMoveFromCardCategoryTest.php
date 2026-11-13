<?php

namespace Tests\Feature\Budget;

use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetMovement;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Category;
use Tests\TestCase;

/**
 * A card payment category's available was read as spending − payments − carry,
 * so a healthy category came out deeply negative. Moving money out of it was
 * then clamped to that negative amount and flipped into a move the other way.
 */
class BudgetMoveFromCardCategoryTest extends TestCase
{
    use RefreshDatabase;

    private const MONTH = '2026-11-01';

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();
        Carbon::setTestNow('2026-11-12');
    }

    /**
     * @return array{0: User, 1: Category, 2: Category}
     */
    private function cardCategoryHolding(float $carry, float $funded, float $payments): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $card = Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Qik',
            'currency_code' => 'USD',
            'credit_closing_day' => 15,
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::CREDIT_CARD)->value('id'),
        ]);
        $cardCategory = Category::where(['team_id' => $team->id, 'account_id' => $card->id])->whereNotNull('parent_id')->firstOrFail();
        $readyToAssign = Category::where(['team_id' => $team->id, 'name' => BudgetReservedNames::READY_TO_ASSIGN->value])->firstOrFail();

        BudgetMonth::updateOrCreate([
            'team_id' => $team->id,
            'category_id' => $cardCategory->id,
            'month' => self::MONTH,
            'name' => self::MONTH,
        ], [
            'user_id' => $user->id,
            'left_from_last_month' => $carry,
            'funded_spending' => $funded,
            'payments' => $payments,
        ]);

        return [$user, $cardCategory, $readyToAssign];
    }

    public function test_card_category_available_adds_the_carry_and_what_was_assigned(): void
    {
        [, $cardCategory] = $this->cardCategoryHolding(51070.77, 19628.99, 22586);
        BudgetMonth::where(['category_id' => $cardCategory->id, 'month' => self::MONTH])->update(['budgeted' => 100]);

        $info = app(BudgetCategoryService::class)->getBudgetInfo($cardCategory, self::MONTH);

        $this->assertEqualsWithDelta(48213.76, $info['available'], 0.001, 'carry 51,070.77 + assigned 100 + funded 19,628.99 - payments 22,586');
    }

    public function test_moving_money_out_of_a_card_category_goes_to_the_destination(): void
    {
        [$user, $cardCategory, $readyToAssign] = $this->cardCategoryHolding(51070.77, 19628.99, 22586);

        $this->actingAs($user)
            ->post("/budgets/{$readyToAssign->id}/months/".self::MONTH, [
                'type' => 'movement',
                'source_category_id' => $cardCategory->id,
                'destination_category_id' => $readyToAssign->id,
                'date' => self::MONTH,
                'amount' => 4912.51,
            ])
            ->assertRedirect();

        $movement = BudgetMovement::where('team_id', $user->current_team_id)->latest('id')->firstOrFail();
        $this->assertSame($cardCategory->id, (int) $movement->source_category_id);
        $this->assertSame($readyToAssign->id, (int) $movement->destination_category_id);
        $this->assertEqualsWithDelta(4912.51, (float) $movement->amount, 0.001);
        $this->assertEqualsWithDelta(-4912.51, (float) BudgetMonth::where(['category_id' => $cardCategory->id, 'month' => self::MONTH])->value('budgeted'), 0.001);
    }
}

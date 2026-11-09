<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\AppCore\Models\Planner;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\NextPaymentsService;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Payee;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * "Qué necesita tu atención" must drop a bill once a verified transaction paid it,
 * however it entered: typed, imported, or an approved draft.
 */
class NextPaymentsPaidDetectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $teamId;

    private Account $bank;

    private Category $group;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-09 10:00:00');
        app(AccountDetailTypesCreate::class)->create();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->teamId = $this->user->ownedTeams()->first()->id;
        $this->user->forceFill(['current_team_id' => $this->teamId])->save();
        $this->bank = Account::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'name' => 'Popular',
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::BANK)->value('id'),
        ]);
        $this->group = Category::create(['name' => 'Casa', 'team_id' => $this->teamId, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_planned_bill_is_paid_by_the_same_category_within_ten_percent(): void
    {
        $light = $this->category('Luz');
        $close = $this->plan($light, 5000, '2026-11-20');
        $this->pay(['category_id' => $light->id, 'total' => 4600, 'date' => '2026-11-08']);
        $this->assertNotListed($close, '2026-11-20');

        $water = $this->category('Agua');
        $far = $this->plan($water, 5000, '2026-11-20');
        $this->pay(['category_id' => $water->id, 'total' => 4000, 'date' => '2026-11-08']);
        $this->assertListed($far, '2026-11-20');
    }

    public function test_payee_or_destination_account_also_pays_a_bill_of_another_category(): void
    {
        $phone = $this->category('Teléfono');
        $claro = Payee::create(['team_id' => $this->teamId, 'user_id' => $this->user->id, 'name' => 'Claro']);
        $byPayee = $this->plan($phone, 2450, '2026-11-15', ['payee_id' => $claro->id]);
        $this->pay(['category_id' => $this->category('Otros')->id, 'payee_id' => $claro->id, 'total' => 2450, 'date' => '2026-11-05']);

        $card = Account::create(['team_id' => $this->teamId, 'user_id' => $this->user->id, 'name' => 'Visa', 'currency_code' => 'DOP', 'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::CREDIT_CARD)->value('id')]);
        $byAccount = $this->plan($this->category('Préstamo'), 8000, '2026-11-18', ['counter_account_id' => $card->id]);
        $this->pay(['counter_account_id' => $card->id, 'total' => 8000, 'date' => '2026-11-06', 'is_transfer' => true]);

        $this->assertNotListed($byPayee, '2026-11-15');
        $this->assertNotListed($byAccount, '2026-11-18');
    }

    public function test_partial_payments_that_add_up_pay_the_bill_but_one_alone_does_not(): void
    {
        $school = $this->category('Colegio');
        $plan = $this->plan($school, 5000, '2026-11-25');

        $this->pay(['category_id' => $school->id, 'total' => 2500, 'date' => '2026-11-02']);
        $this->assertListed($plan, '2026-11-25');

        $this->pay(['category_id' => $school->id, 'total' => 2500, 'date' => '2026-11-07']);
        $this->assertNotListed($plan, '2026-11-25');
    }

    public function test_a_draft_does_not_count_until_it_is_approved_and_then_the_plan_moves_on(): void
    {
        $rent = $this->category('Alquiler');
        $plan = $this->plan($rent, 25000, '2026-11-20');
        $draft = $this->pay(['category_id' => $rent->id, 'total' => 25000, 'date' => '2026-11-05', 'status' => Transaction::STATUS_DRAFT]);
        $this->assertListed($plan, '2026-11-20');

        Sanctum::actingAs($this->user);
        $this->postJson("/api/mobile/transactions/{$draft->id}/approve")->assertOk();

        $this->assertNotListed($plan, '2026-11-20');
        $plan->refresh();
        $this->assertSame('2026-12-20', substr((string) $plan->date, 0, 10));
        $this->assertSame($draft->id, (int) $plan->schedule->completed_resource_id);
        $this->assertNull($plan->schedule->completed_at);
        $this->assertListed($plan, '2026-12-20');
    }

    public function test_an_overdue_plan_stays_listed_until_a_late_payment_settles_it(): void
    {
        $gas = $this->category('Gas');
        $plan = $this->plan($gas, 1800, '2026-10-25');
        $ancient = $this->plan($this->category('Viejo'), 900, '2026-08-10');

        $overdue = $this->payments()->firstWhere('id', "planned_{$plan->id}");
        $this->assertSame('overdue', $overdue['status']);
        $this->assertSame('2026-10-25', $overdue['due_date']);
        $this->assertFalse($this->payments()->contains('id', "planned_{$ancient->id}"));

        $this->pay(['category_id' => $gas->id, 'total' => 1800, 'date' => '2026-11-03']);

        $this->assertNotListed($plan, '2026-10-25');
        $this->assertSame('2026-11-25', substr((string) $plan->fresh()->date, 0, 10));
    }

    public function test_budget_reminder_needs_a_verified_payment_close_to_its_amount(): void
    {
        $internet = $this->category('Internet');
        BudgetTarget::create(['team_id' => $this->teamId, 'user_id' => $this->user->id, 'category_id' => $internet->id, 'target_type' => BudgetTarget::TYPE_SPENDING, 'amount' => 3000, 'frequency' => 'monthly', 'frequency_month_date' => 20, 'notify' => true]);

        $this->pay(['category_id' => $internet->id, 'total' => 3000, 'date' => '2026-11-03', 'status' => Transaction::STATUS_DRAFT]);
        $this->pay(['category_id' => $internet->id, 'total' => 1000, 'date' => '2026-11-04']);
        $this->assertTrue($this->payments()->contains(fn (array $payment) => $payment['type'] === 'budget_category' && $payment['category_id'] === $internet->id));

        $this->pay(['category_id' => $internet->id, 'total' => 3000, 'date' => '2026-11-06']);
        $this->assertFalse($this->payments()->contains(fn (array $payment) => $payment['type'] === 'budget_category' && $payment['category_id'] === $internet->id));
    }

    private function category(string $name): Category
    {
        return Category::create(['name' => $name, 'team_id' => $this->teamId, 'resource_type' => 'transactions', 'parent_id' => $this->group->id, 'depth' => 1, 'index' => 0]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function plan(Category $category, float $total, string $date, array $extra = []): Transaction
    {
        $plan = Transaction::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'account_id' => $this->bank->id,
            'category_id' => $category->id,
            'date' => $date,
            'description' => $category->name,
            'total' => $total,
            'direction' => Transaction::DIRECTION_CREDIT,
            'currency_code' => 'DOP',
            'status' => Transaction::STATUS_PLANNED,
            ...$extra,
        ]);
        Planner::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'dateable_id' => $plan->id,
            'dateable_type' => Transaction::class,
            'frequency' => 'MONTHLY',
            'date' => $date,
            'end_date' => '2030-12-31',
            'automatic' => false,
        ]);

        return $plan;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function pay(array $attributes): Transaction
    {
        return Transaction::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'account_id' => $this->bank->id,
            'description' => 'Pago',
            'direction' => Transaction::DIRECTION_CREDIT,
            'currency_code' => 'DOP',
            'status' => Transaction::STATUS_VERIFIED,
            ...$attributes,
        ]);
    }

    private function payments()
    {
        return app(NextPaymentsService::class)->getNextPayments($this->teamId);
    }

    private function assertListed(Transaction $plan, string $dueDate): void
    {
        $this->assertTrue($this->payments()->contains(fn (array $payment) => $payment['id'] === "planned_{$plan->id}" && $payment['due_date'] === $dueDate), "planned_{$plan->id} due {$dueDate} should be listed");
    }

    private function assertNotListed(Transaction $plan, string $dueDate): void
    {
        $this->assertFalse($this->payments()->contains(fn (array $payment) => $payment['id'] === "planned_{$plan->id}" && $payment['due_date'] === $dueDate), "planned_{$plan->id} due {$dueDate} should be paid");
    }
}

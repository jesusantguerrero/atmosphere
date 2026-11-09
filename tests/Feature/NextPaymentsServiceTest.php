<?php

namespace Tests\Feature;

use App\Domains\AppCore\Models\Category;
use App\Domains\AppCore\Models\Planner;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\NextPaymentsService;
use App\Models\Account;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\AccountDetailType;
use Tests\TestCase;

class NextPaymentsServiceTest extends TestCase
{
    use RefreshDatabase;

    private NextPaymentsService $service;

    private User $user;

    private int $teamId;

    private Category $group;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-09 10:00:00');
        (new AccountDetailTypesCreate)->create();

        $this->service = app(NextPaymentsService::class);
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->teamId = $this->user->ownedTeams()->first()->id;
        $this->user->forceFill(['current_team_id' => $this->teamId])->save();
        $this->group = Category::create(['name' => 'Casa', 'team_id' => $this->teamId, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_gets_next_payments_from_all_sources(): void
    {
        $this->budgetTarget('Luz', 15, 100);
        $card = $this->cardWithDebt(closingDay: 3, debt: 7000);
        $this->plannedTransaction('Netflix', '2026-11-19', 75);

        $types = $this->service->getNextPayments($this->teamId)->pluck('type')->unique()->sort()->values()->all();

        $this->assertSame(['budget_category', 'credit_card_payment', 'planned_transaction'], $types);
        $this->assertTrue($this->service->getNextPayments($this->teamId)->contains(fn (array $payment) => ($payment['account_id'] ?? null) === $card->id));
    }

    public function test_payments_are_sorted_by_due_date(): void
    {
        $this->budgetTarget('Luz', 20, 100);
        $this->plannedTransaction('Netflix', '2026-11-12', 75);

        $dueDates = $this->service->getNextPayments($this->teamId)->pluck('due_date')->map(fn ($date) => substr((string) $date, 0, 10))->all();

        $this->assertSame(['2026-11-12', '2026-11-20'], $dueDates);
    }

    public function test_excludes_already_paid_budget_categories(): void
    {
        $target = $this->budgetTarget('Luz', 15, 100);

        Transaction::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'category_id' => $target->category_id,
            'date' => '2026-11-05',
            'description' => 'Luz',
            'total' => 100,
            'direction' => Transaction::DIRECTION_CREDIT,
            'currency_code' => 'DOP',
            'status' => Transaction::STATUS_VERIFIED,
        ]);

        $this->assertCount(0, $this->service->getNextPayments($this->teamId)->where('type', 'budget_category'));
    }

    private function budgetTarget(string $name, int $dueDay, float $amount): BudgetTarget
    {
        $category = Category::create(['name' => $name, 'team_id' => $this->teamId, 'resource_type' => 'transactions', 'parent_id' => $this->group->id, 'depth' => 1, 'index' => 0]);

        return BudgetTarget::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'target_type' => BudgetTarget::TYPE_SPENDING,
            'amount' => $amount,
            'frequency' => 'monthly',
            'frequency_month_date' => $dueDay,
            'notify' => true,
        ]);
    }

    private function plannedTransaction(string $description, string $date, float $total): Transaction
    {
        $planned = Transaction::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'date' => $date,
            'description' => $description,
            'total' => $total,
            'direction' => Transaction::DIRECTION_CREDIT,
            'currency_code' => 'DOP',
            'status' => Transaction::STATUS_PLANNED,
        ]);
        Planner::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'dateable_id' => $planned->id,
            'dateable_type' => Transaction::class,
            'frequency' => 'MONTHLY',
            'date' => $date,
            'end_date' => '2030-12-31',
            'automatic' => false,
        ]);

        return $planned;
    }

    private function cardWithDebt(int $closingDay, float $debt): Account
    {
        $card = Account::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'name' => 'Visa',
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::CREDIT_CARD)->value('id'),
            'credit_closing_day' => $closingDay,
            'credit_limit' => 50000,
        ]);

        $date = now()->subMonths(2)->format('Y-m-d');
        $transactionId = DB::table('transactions')->insertGetId([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'account_id' => $card->id,
            'date' => $date,
            'description' => 'Compra',
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => $debt,
            'currency_code' => 'DOP',
            'status' => Transaction::STATUS_VERIFIED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('transaction_lines')->insert([
            'transaction_id' => $transactionId,
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'account_id' => $card->id,
            'date' => $date,
            'type' => -1,
            'amount' => $debt,
            'anchor' => 1,
            'index' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $card;
    }
}

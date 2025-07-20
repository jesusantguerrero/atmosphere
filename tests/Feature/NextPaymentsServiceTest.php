<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Transaction\Models\BillingCycle;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\NextPaymentsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NextPaymentsServiceTest extends TestCase
{
    use RefreshDatabase;

    private NextPaymentsService $service;
    private User $user;
    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(NextPaymentsService::class);
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->user->current_team_id = $this->team->id;
        $this->user->save();
    }

    public function test_gets_next_payments_from_all_sources()
    {
        // Create test data for each source
        $budgetTarget = BudgetTarget::factory()->create([
            'team_id' => $this->team->id,
            'target_type' => 'spending',
            'frequency_month_date' => 15,
            'amount' => 100.00,
        ]);

        $billingCycle = BillingCycle::factory()->create([
            'team_id' => $this->team->id,
            'due_at' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'status' => 'pending',
            'minimum_payment' => 50.00,
        ]);

        $plannedTransaction = Transaction::factory()->create([
            'team_id' => $this->team->id,
            'status' => Transaction::STATUS_PLANNED,
            'date' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'total' => 75.00,
        ]);

        $nextPayments = $this->service->getNextPayments($this->team->id);

        $this->assertCount(3, $nextPayments);
        
        $types = $nextPayments->pluck('type')->toArray();
        $this->assertContains('budget_category', $types);
        $this->assertContains('credit_card_payment', $types);
        $this->assertContains('planned_transaction', $types);
    }

    public function test_payments_are_sorted_by_due_date()
    {
        // Create payments with different due dates
        BudgetTarget::factory()->create([
            'team_id' => $this->team->id,
            'target_type' => 'spending',
            'frequency_month_date' => 20, // Later in month
            'amount' => 100.00,
        ]);

        BillingCycle::factory()->create([
            'team_id' => $this->team->id,
            'due_at' => Carbon::now()->addDays(5)->format('Y-m-d'), // Earlier
            'status' => 'pending',
            'minimum_payment' => 50.00,
        ]);

        $nextPayments = $this->service->getNextPayments($this->team->id);

        // First payment should be the billing cycle (earlier due date)
        $this->assertEquals('credit_card_payment', $nextPayments->first()['type']);
    }

    public function test_excludes_already_paid_budget_categories()
    {
        $budgetTarget = BudgetTarget::factory()->create([
            'team_id' => $this->team->id,
            'target_type' => 'spending',
            'frequency_month_date' => 15,
            'amount' => 100.00,
        ]);

        // Create a transaction for this month in the same category
        Transaction::factory()->create([
            'team_id' => $this->team->id,
            'category_id' => $budgetTarget->category_id,
            'date' => Carbon::now()->format('Y-m-d'),
            'status' => Transaction::STATUS_VERIFIED,
        ]);

        $nextPayments = $this->service->getNextPayments($this->team->id);

        // Should not include the budget category since it's already paid
        $budgetPayments = $nextPayments->where('type', 'budget_category');
        $this->assertCount(0, $budgetPayments);
    }
}
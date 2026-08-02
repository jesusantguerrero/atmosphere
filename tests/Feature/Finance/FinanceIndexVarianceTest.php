<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the payload shape behind the "vs Jul: Infinity%" bug.
 *
 * `getIncome()` uses ->sum() and returns 0 for an empty period, but
 * `getExpensesTotal()` reads `total_amount` off a SUM() aggregate, which MySQL
 * returns as NULL when no rows match. That null reached the frontend as the
 * previous-month baseline and divided into Infinity. These tests pin both
 * totals to a real number so the baseline is never null again.
 */
class FinanceIndexVarianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_totals_are_numeric_when_team_has_no_transactions(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/finance');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertIsFloat($page['props']['lastMonthExpenses']);
        $this->assertIsFloat($page['props']['transactionTotal']);
        $this->assertSame(0.0, $page['props']['lastMonthExpenses']);
        $this->assertSame(0.0, $page['props']['transactionTotal']);
    }

    public function test_last_month_expenses_is_never_null(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/finance');

        $response->assertOk();

        $this->assertNotNull($response->viewData('page')['props']['lastMonthExpenses']);
    }
}

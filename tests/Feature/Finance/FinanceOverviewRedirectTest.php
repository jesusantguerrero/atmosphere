<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Finance has no overview page of its own: /finance opens the budget, and the
 * old links keep working.
 */
class FinanceOverviewRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_redirects_to_the_budget(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get('/finance')
            ->assertRedirect(route('budgets.index'));
    }

    public function test_finance_redirect_keeps_the_query_string(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get('/finance?filter%5Bdate%5D=2026-11-01~2026-11-30')
            ->assertRedirect(route('budgets.index', ['filter' => ['date' => '2026-11-01~2026-11-30']]));
    }

    public function test_finance_overview_route_name_points_at_the_redirect(): void
    {
        $this->assertSame(url('/finance'), route('finance.overview'));
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/finance')->assertRedirect(route('login'));
    }
}

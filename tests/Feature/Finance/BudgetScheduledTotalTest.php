<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BudgetScheduledTotalTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_page_exposes_scheduled_total_prop(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->get('/budgets')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/Budget')
                ->has('scheduledTotal')
            );
    }

    public function test_scheduled_total_is_zero_when_no_planned_payments_exist(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->get('/budgets')
            ->assertInertia(fn (Assert $page) => $page
                ->where('scheduledTotal', 0)
            );
    }
}

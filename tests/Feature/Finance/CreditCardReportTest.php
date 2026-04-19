<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreditCardReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_cards_trend_does_not_divide_by_zero_when_no_credit_limit(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $response = $this->get('/trends/credit-cards');

        $response->assertOk();
    }

    public function test_credit_cards_payload_exposes_capacity_and_usage(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->get('/trends/credit-cards')
            ->assertInertia(fn (Assert $page) => $page
                ->has('data.creditCapacity')
                ->has('data.creditTotal')
                ->has('data.creditLineUsage')
                ->missing('data.topTransaction')
                ->missing('data.billingCyclePayments')
            );
    }

    public function test_credit_cards_payload_exposes_mom_delta_fields(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->get('/trends/credit-cards')
            ->assertInertia(fn (Assert $page) => $page
                ->has('data.creditTotalPrevious')
                ->has('data.creditTotalDelta')
                ->where('data.creditTotalDelta', 0)
            );
    }

    public function test_credit_cards_payload_exposes_billing_cycles_discount_total(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->get('/trends/credit-cards')
            ->assertInertia(fn (Assert $page) => $page
                ->has('data.billingCyclesByCard.discountTotal')
                ->has('data.billingCyclesByCard.total')
                ->has('data.billingCyclesByCard.subtotal')
            );
    }

    public function test_credit_cards_accepts_date_and_account_filters_without_error(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->get('/trends/credit-cards?filter[date]=2025-01-01~2025-01-31&filter[account]=99,100')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.lastCycleBalances', [])
                ->where('data.creditTotal', 0)
            );
    }
}

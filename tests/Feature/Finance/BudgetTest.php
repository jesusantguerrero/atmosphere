<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_budgets_page_renders_with_expected_props(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->get('/budgets')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/Budget')
                ->has('budgets')
                ->has('accountTotal')
                ->has('serverSearchOptions')
                ->missing('distribution')
                ->missing('available')
            );
    }

    public function test_budgets_response_is_not_corrupted_by_echo_output(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $response = $this->get('/budgets');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringNotContainsString('TBB:', $content);
        $this->assertStringNotContainsString('updated month', $content);
    }

    public function test_budgets_page_exposes_category_averages_prop(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->get('/budgets')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('categoryAverages'));
    }
}

<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_budget_info()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->get('/budgets');
        // ->assertInertia(fn (Assert $page) => $page
        // ->component('Finance/Budget')
        // ->has('budgets')
        // ->has('accounts')
        // ->has('categories'));
    }
}

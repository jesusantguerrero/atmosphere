<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_budget_info()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/budgets')
        ->assertInertia(fn (Assert $page) => $page
        ->component('Finance/Budget')
        ->has('budgets')
        ->has('accounts')
        ->has('categories'));
    }
}

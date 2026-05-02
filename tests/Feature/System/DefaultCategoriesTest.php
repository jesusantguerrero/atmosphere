<?php

namespace Tests\Feature\System;

use App\Domains\AppCore\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_team_seeds_savings_and_personal_categories(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $names = Category::where([
            'team_id' => $team->id,
            'resource_type' => 'transactions',
        ])->pluck('name')->all();

        $this->assertContains('Inflow', $names);
        $this->assertContains('Ready to Assign', $names);
        $this->assertContains('Savings', $names);
        $this->assertContains('Ahorro', $names);
        $this->assertContains('Personal', $names);
        $this->assertContains('Gasto Personal', $names);
    }

    public function test_savings_and_personal_have_correct_parent_groups(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $savingsGroup = Category::where(['team_id' => $team->id, 'name' => 'Savings'])->first();
        $personalGroup = Category::where(['team_id' => $team->id, 'name' => 'Personal'])->first();

        $this->assertNotNull($savingsGroup);
        $this->assertNotNull($personalGroup);
        $this->assertNull($savingsGroup->parent_id);
        $this->assertNull($personalGroup->parent_id);

        $ahorro = Category::where(['team_id' => $team->id, 'name' => 'Ahorro'])->first();
        $gastoPersonal = Category::where(['team_id' => $team->id, 'name' => 'Gasto Personal'])->first();

        $this->assertSame($savingsGroup->id, $ahorro->parent_id);
        $this->assertSame($personalGroup->id, $gastoPersonal->parent_id);
    }
}

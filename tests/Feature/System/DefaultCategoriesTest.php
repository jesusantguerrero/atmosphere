<?php

namespace Tests\Feature\System;

use App\Domains\AppCore\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_team_seeds_the_seven_default_category_groups(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $groupNames = Category::where([
            'team_id' => $team->id,
            'resource_type' => 'transactions',
        ])->whereNull('parent_id')->pluck('name')->all();

        // Full YNAB-style priorities plus Inflow (system) and Personal (host
        // for the personal_spending default role). Renaming these breaks
        // BudgetReservedNames / setBudgetSplitDefaults, so treat them as a
        // stable contract.
        $this->assertContains('Inflow', $groupNames);
        $this->assertContains('Immediate Obligations', $groupNames);
        $this->assertContains('True Expenses', $groupNames);
        $this->assertContains('Quality of Life Goals', $groupNames);
        $this->assertContains('Just for Fun', $groupNames);
        $this->assertContains('Savings', $groupNames);
        $this->assertContains('Personal', $groupNames);
    }

    public function test_reserved_display_ids_are_preserved(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        foreach (['ready_to_assign', 'savings_general', 'personal_spending'] as $slug) {
            $category = Category::where([
                'team_id' => $team->id,
                'display_id' => $slug,
            ])->first();

            $this->assertNotNull(
                $category,
                "Reserved category slug '{$slug}' is missing after seed — code depends on it."
            );
        }
    }

    public function test_ready_to_assign_name_is_preserved(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        // Many BudgetMonth queries filter on the literal string
        // 'Ready to Assign' via BudgetReservedNames::READY_TO_ASSIGN->value.
        // Renaming the seed row would silently break those queries.
        $exists = Category::where([
            'team_id' => $team->id,
            'name' => 'Ready to Assign',
        ])->exists();

        $this->assertTrue($exists, 'The category named "Ready to Assign" must exist verbatim.');
    }

    public function test_immediate_obligations_group_has_common_sub_categories(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $parent = Category::where(['team_id' => $team->id, 'name' => 'Immediate Obligations'])->first();
        $subs = Category::where(['team_id' => $team->id, 'parent_id' => $parent->id])->pluck('name')->all();

        // Not exhaustive — just the ones that would make a newcomer's Budget
        // usable on day one without inventing categories.
        foreach (['Rent / Mortgage', 'Electricity', 'Water', 'Groceries', 'Transportation'] as $expected) {
            $this->assertContains($expected, $subs, "Missing default sub-category: {$expected}");
        }
    }

    public function test_true_expenses_group_seeds_sinking_fund_style_categories(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $parent = Category::where(['team_id' => $team->id, 'name' => 'True Expenses'])->first();
        $subs = Category::where(['team_id' => $team->id, 'parent_id' => $parent->id])->pluck('name')->all();

        foreach (['Auto Maintenance', 'Home Maintenance', 'Medical', 'Insurance', 'Clothing', 'Gifts'] as $expected) {
            $this->assertContains($expected, $subs, "Missing True Expenses sub-category: {$expected}");
        }
    }

    public function test_savings_group_has_emergency_fund_and_general_savings(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $parent = Category::where(['team_id' => $team->id, 'name' => 'Savings'])->first();
        $subs = Category::where(['team_id' => $team->id, 'parent_id' => $parent->id])->pluck('name')->all();

        $this->assertContains('Emergency Fund', $subs);
        $this->assertContains('General Savings', $subs);
    }

    public function test_personal_group_holds_personal_spending_default_role(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $parent = Category::where(['team_id' => $team->id, 'name' => 'Personal'])->first();
        $child = Category::where(['team_id' => $team->id, 'display_id' => 'personal_spending'])->first();

        $this->assertNotNull($parent);
        $this->assertNotNull($child);
        $this->assertSame($parent->id, $child->parent_id);
    }

    public function test_seed_uses_spanish_names_when_app_locale_is_es(): void
    {
        // Simulate a Spanish signup: the request-scoped locale drives what
        // TransactionCategoriesCreate writes into `categories.name`.
        app()->setLocale('es');

        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $groceries = Category::where(['team_id' => $team->id, 'display_id' => 'groceries'])->firstOrFail();
        $emergency = Category::where(['team_id' => $team->id, 'display_id' => 'emergency_fund'])->firstOrFail();
        $obligations = Category::where(['team_id' => $team->id, 'display_id' => 'immediate_obligations'])->firstOrFail();
        $savings = Category::where(['team_id' => $team->id, 'display_id' => 'personal_spending'])->firstOrFail();

        $this->assertSame('Supermercado', $groceries->name);
        $this->assertSame('Fondo de Emergencia', $emergency->name);
        $this->assertSame('Obligaciones Inmediatas', $obligations->name);
        $this->assertSame('Gasto Personal', $savings->name);

        app()->setLocale('en');
    }

    public function test_reserved_names_stay_english_even_when_locale_is_es(): void
    {
        // Ready to Assign and Inflow are matched literally in BudgetMonth
        // queries; localising them would silently break every RTA lookup.
        app()->setLocale('es');

        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $inflow = Category::where(['team_id' => $team->id, 'display_id' => 'inflow'])->firstOrFail();
        $rta = Category::where(['team_id' => $team->id, 'display_id' => 'ready_to_assign'])->firstOrFail();

        $this->assertSame('Inflow', $inflow->name);
        $this->assertSame('Ready to Assign', $rta->name);

        app()->setLocale('en');
    }
}

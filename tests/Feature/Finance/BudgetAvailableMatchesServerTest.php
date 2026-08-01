<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Regression for LM-4: the frontend used to recompute `available` client-side via
 * `parseAvailable` (`budgeted + left_from_last_month - |activity|`), which dropped
 * `moved_from_last_month`, mistreated positive activity, and ignored payments.
 *
 * After deletion, the budget page must expose the server-side `BudgetMonth.available`
 * verbatim — no client mutation.
 */
class BudgetAvailableMatchesServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_payload_available_matches_budget_month_available(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        // Reference by display_id (stable slug) instead of display name so
        // this test doesn't break every time the seed's user-visible names change.
        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();

        // Seed values that would diverge under the old `parseAvailable` formula:
        // server stores 200, but the old client formula would compute
        //    50 (budgeted) + 0 (left) - |200| (activity) = -150.
        BudgetMonth::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $ahorro->id,
            'month' => now()->startOfMonth()->format('Y-m-d'),
            'name' => now()->startOfMonth()->format('Y-m-d'),
            'budgeted' => 50,
            'activity' => 200,
            'available' => 200,
            'left_from_last_month' => 0,
            'currency_code' => 'DOP',
        ]);

        $this->actingAs($user)
            ->get('/budgets')
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($ahorro) {
                $page->component('Finance/Budget');

                $budgets = collect($page->toArray()['props']['budgets']['data'] ?? []);
                $this->assertNotEmpty($budgets, 'budgets prop should be populated');

                $found = null;
                foreach ($budgets as $group) {
                    foreach ($group['subCategories'] ?? [] as $sub) {
                        if (($sub['id'] ?? null) === $ahorro->id) {
                            $found = $sub;
                            break 2;
                        }
                    }
                }

                $this->assertNotNull($found, 'Ahorro subcategory should appear in the budget payload');
                $this->assertSame(200.0, (float) $found['available'], 'available must match BudgetMonth.available, not the legacy parseAvailable formula');
            });
    }
}

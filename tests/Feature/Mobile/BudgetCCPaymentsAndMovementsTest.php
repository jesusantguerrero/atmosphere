<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMovement;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BudgetCCPaymentsAndMovementsTest extends TestCase
{
    use RefreshDatabase;

    // ── CC Payment enrichment ────────────────────────────────────

    public function test_budget_response_includes_payment_for_cc_category(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        // Create a credit card account with negative balance = owing 5000.
        $ccAccount = Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Visa Gold',
            'currency_code' => 'DOP',
            'balance_type' => 'CREDIT',
            'type' => -1,
            'credit_closing_day' => 15,
        ]);

        // CC Payments group.
        $ccGroup = Category::where('team_id', $team->id)
            ->whereNull('parent_id')
            ->where('name', BudgetReservedNames::CREDIT_CARD_PAYMENTS->value)
            ->first();

        if (! $ccGroup) {
            $ccGroup = Category::create([
                'name' => BudgetReservedNames::CREDIT_CARD_PAYMENTS->value,
                'team_id' => $team->id,
                'resource_type' => 'transactions',
                'parent_id' => null,
                'depth' => 0,
                'index' => 98,
            ]);
        }

        // Subcategory linked to the CC account.
        $ccCat = Category::create([
            'name' => 'Visa Gold Payment',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $ccGroup->id,
            'account_id' => $ccAccount->id,
            'depth' => 1,
            'index' => 0,
        ]);

        $response = $this->getJson('/api/mobile/budget?month='.now()->format('Y-m'))
            ->assertOk();

        // Find the CC group and its child.
        $found = false;
        foreach ($response->json('groups') as $group) {
            if ($group['kind'] === 'credit_card_payments') {
                foreach ($group['children'] as $child) {
                    if ($child['id'] === $ccCat->id) {
                        $this->assertNotNull($child['payment']);
                        $this->assertEquals($ccAccount->id, $child['payment']['account_id']);
                        $this->assertArrayHasKey('owed', $child['payment']);
                        $this->assertArrayHasKey('set_aside', $child['payment']);
                        $this->assertArrayHasKey('gap', $child['payment']);
                        $this->assertArrayHasKey('coverage_pct', $child['payment']);
                        $this->assertArrayHasKey('ready_to_pay', $child['payment']);
                        $found = true;
                    }
                }
            }
        }

        $this->assertTrue($found, 'CC payment category not found in response.');
    }

    public function test_non_cc_category_has_null_payment(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = Category::create([
            'name' => 'Bills',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => 99,
        ]);

        Category::create([
            'name' => 'Electric',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        $response = $this->getJson('/api/mobile/budget?month='.now()->format('Y-m'))
            ->assertOk();

        foreach ($response->json('groups') as $g) {
            foreach ($g['children'] as $child) {
                if ($child['name'] === 'Electric') {
                    $this->assertNull($child['payment']);
                }
            }
        }
    }

    // ── Budget Movements ─────────────────────────────────────────

    public function test_list_movements_for_category(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = Category::create([
            'name' => 'G',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => 99,
        ]);

        $catA = Category::create([
            'name' => 'A',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        $catB = Category::create([
            'name' => 'B',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 1,
        ]);

        (new BudgetMovement)->forceFill([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'source_category_id' => $catA->id,
            'source_category_name' => 'A',
            'destination_category_id' => $catB->id,
            'destination_category_name' => 'B',
            'amount' => 500,
            'date' => now()->format('Y-m-d'),
        ])->save();

        $month = now()->format('Y-m');
        $response = $this->getJson("/api/mobile/budget/categories/{$catA->id}/movements?month={$month}")
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('movement', $response->json('data.0.type'));
        $this->assertEquals(500, $response->json('data.0.amount'));
    }

    public function test_destroy_movement(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = Category::create([
            'name' => 'G',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => 99,
        ]);

        $catA = Category::create([
            'name' => 'Source',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        $catB = Category::create([
            'name' => 'Dest',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 1,
        ]);

        $movement = new BudgetMovement;
        $movement->forceFill([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'source_category_id' => $catA->id,
            'destination_category_id' => $catB->id,
            'amount' => 100,
            'date' => now()->format('Y-m-d'),
        ]);
        $movement->save();

        $this->deleteJson("/api/mobile/budget/movements/{$movement->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull(BudgetMovement::find($movement->id));
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function makeUserAndTeam(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        return [$user->fresh(), $team];
    }
}

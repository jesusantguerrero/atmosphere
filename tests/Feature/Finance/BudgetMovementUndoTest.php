<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Data\BudgetAssignData;
use App\Domains\Budget\Data\BudgetMovementData;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetMovement;
use App\Domains\Budget\Services\BudgetMovementService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LM-5 — undo for budget movements. Covers the "bad click silently desbalances
 * you" pain: register a movement, undo it, balances revert to pre-movement state
 * and the movement row is gone.
 */
class BudgetMovementUndoTest extends TestCase
{
    use RefreshDatabase;

    private function categoryFor(int $teamId, int $userId, string $name): Category
    {
        // Create as a top-level group (depth 0, no parent) to avoid the
        // journal package's setNumber() requiring a parent category record.
        return Category::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'name' => $name,
            'display_id' => str()->slug($name, '_'),
            'resource_type' => 'transactions',
            'depth' => 0,
            'parent_id' => null,
        ]);
    }

    public function test_revert_movement_restores_balances_and_deletes_row(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $service = app(BudgetMovementService::class);

        $source = $this->categoryFor($team->id, $user->id, 'Source');
        $destination = $this->categoryFor($team->id, $user->id, 'Destination');

        $month = now()->startOfMonth()->format('Y-m-d');
        $date = now()->format('Y-m-d');

        // Seed source with $1000 budgeted via the assignment primitive.
        $service->registerAssignment(new BudgetAssignData(
            $team->id, $user->id, $date, $source->id, 1000,
        ));

        // Move $300 source → destination
        $service->registerMovement(BudgetMovementData::from([
            'id' => null,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'source_category_id' => $source->id,
            'destination_category_id' => $destination->id,
            'type' => 'movement',
            'date' => $date,
            'amount' => 300,
        ]));

        $sourceAfter = BudgetMonth::where(['category_id' => $source->id, 'month' => $month])->value('budgeted');
        $destinationAfter = BudgetMonth::where(['category_id' => $destination->id, 'month' => $month])->value('budgeted');
        $this->assertEqualsWithDelta(700, (float) $sourceAfter, 0.01);
        $this->assertEqualsWithDelta(300, (float) $destinationAfter, 0.01);

        $movement = BudgetMovement::where('source_category_id', $source->id)
            ->where('destination_category_id', $destination->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($movement);

        $this->actingAs($user)
            ->delete("/budget-movements/{$movement->id}")
            ->assertRedirect();

        $this->assertNull(BudgetMovement::find($movement->id), 'Movement row should be deleted');

        $sourceFinal = BudgetMonth::where(['category_id' => $source->id, 'month' => $month])->value('budgeted');
        $destinationFinal = BudgetMonth::where(['category_id' => $destination->id, 'month' => $month])->value('budgeted');
        $this->assertEqualsWithDelta(1000, (float) $sourceFinal, 0.01, 'Source balance must return to pre-movement state');
        $this->assertEqualsWithDelta(0, (float) $destinationFinal, 0.01, 'Destination balance must return to pre-movement state');
    }

    public function test_revert_endpoint_blocks_users_from_other_teams(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $stranger = User::factory()->withPersonalTeam()->create();
        $service = app(BudgetMovementService::class);

        $team = $owner->ownedTeams()->first();
        $source = $this->categoryFor($team->id, $owner->id, 'Source');
        $destination = $this->categoryFor($team->id, $owner->id, 'Destination');

        $service->registerAssignment(new BudgetAssignData(
            $team->id, $owner->id, now()->format('Y-m-d'), $source->id, 500,
        ));
        $service->registerMovement(BudgetMovementData::from([
            'id' => null,
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'source_category_id' => $source->id,
            'destination_category_id' => $destination->id,
            'type' => 'movement',
            'date' => now()->format('Y-m-d'),
            'amount' => 100,
        ]));

        $movement = BudgetMovement::latest('id')->first();

        $this->actingAs($stranger)
            ->delete("/budget-movements/{$movement->id}")
            ->assertForbidden();

        $this->assertNotNull(BudgetMovement::find($movement->id), 'Movement must remain when caller is unauthorized');
    }

    public function test_assign_endpoint_flashes_movement_id_for_undo_toast(): void
    {
        $user = $this->seededUserWithReadyToAssign();
        $team = $user->ownedTeams()->first();
        $category = $this->categoryFor($team->id, $user->id, 'Groceries');
        $month = now()->startOfMonth()->format('Y-m-d');

        $response = $this->actingAs($user)->post("/budgets/{$category->id}/months/{$month}", [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'budgeted' => 250,
            'date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();

        $movement = BudgetMovement::latest('id')->first();
        $this->assertNotNull($movement, 'Assign should have created a BudgetMovement');

        $response->assertSessionHas('flash', [
            'type' => 'movement_created',
            'movement_id' => $movement->id,
        ]);
    }

    private function seededUserWithReadyToAssign(): User
    {
        return User::factory()->withPersonalTeam()->create();
    }

    public function test_revert_endpoint_requires_authentication(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $service = app(BudgetMovementService::class);
        $team = $owner->ownedTeams()->first();
        $source = $this->categoryFor($team->id, $owner->id, 'S');
        $destination = $this->categoryFor($team->id, $owner->id, 'D');

        $service->registerAssignment(new BudgetAssignData(
            $team->id, $owner->id, now()->format('Y-m-d'), $source->id, 500,
        ));
        $service->registerMovement(BudgetMovementData::from([
            'id' => null,
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'source_category_id' => $source->id,
            'destination_category_id' => $destination->id,
            'type' => 'movement',
            'date' => now()->format('Y-m-d'),
            'amount' => 50,
        ]));

        $movement = BudgetMovement::latest('id')->first();

        $this->delete("/budget-movements/{$movement->id}")
            ->assertRedirect('/login');
    }
}

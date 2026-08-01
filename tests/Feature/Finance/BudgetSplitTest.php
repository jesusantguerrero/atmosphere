<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetSplitTest extends TestCase
{
    use RefreshDatabase;

    public function test_split_creates_one_movement_per_destination_in_a_single_request(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $this->actingAs($user);

        $rta = Category::where(['team_id' => $team->id, 'name' => 'Ready to Assign'])->firstOrFail();
        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();
        $gastoPersonal = Category::where(['team_id' => $team->id, 'display_id' => 'personal_spending'])->firstOrFail();

        $month = now()->format('Y-m-01');

        $response = $this->post("/budgets/{$rta->id}/months/{$month}/split", [
            'date' => $month,
            'splits' => [
                ['destination_category_id' => $ahorro->id, 'amount' => 100],
                ['destination_category_id' => $gastoPersonal->id, 'amount' => 50],
            ],
        ]);

        $response->assertRedirect();

        $movements = BudgetMovement::where('team_id', $team->id)->get();
        $this->assertCount(2, $movements);

        $byDestination = $movements->keyBy('destination_category_id');
        $this->assertEquals(100, (float) $byDestination[$ahorro->id]->amount);
        $this->assertEquals(50, (float) $byDestination[$gastoPersonal->id]->amount);
    }

    public function test_split_validates_destination_category_exists(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $this->actingAs($user);

        $rta = Category::where(['team_id' => $team->id, 'name' => 'Ready to Assign'])->firstOrFail();
        $month = now()->format('Y-m-01');

        $this->post("/budgets/{$rta->id}/months/{$month}/split", [
            'date' => $month,
            'splits' => [
                ['destination_category_id' => 999999, 'amount' => 50],
            ],
        ])->assertSessionHasErrors('splits.0.destination_category_id');
    }

    public function test_split_rejects_zero_or_negative_amounts(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $this->actingAs($user);

        $rta = Category::where(['team_id' => $team->id, 'name' => 'Ready to Assign'])->firstOrFail();
        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();
        $month = now()->format('Y-m-01');

        $this->post("/budgets/{$rta->id}/months/{$month}/split", [
            'date' => $month,
            'splits' => [
                ['destination_category_id' => $ahorro->id, 'amount' => 0],
            ],
        ])->assertSessionHasErrors('splits.0.amount');
    }
}

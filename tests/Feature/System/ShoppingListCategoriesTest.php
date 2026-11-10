<?php

namespace Tests\Feature\System;

use App\Events\ShoppingListItemUpdated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanStage;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

/**
 * The authed shopping page renames and deletes categories and moves items
 * between them; those calls had no web routes, so they failed silently.
 */
class ShoppingListCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([ShoppingListItemUpdated::class]);

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->plan = app(PlanService::class)
            ->createPlanBoard($this->user->currentTeam, PlanTypes::SHOPPING_LIST, 'Weekly groceries')
            ->fresh(['stages.items']);
    }

    public function test_a_category_can_be_renamed(): void
    {
        $stage = $this->plan->stages->first();

        $this->actingAs($this->user)
            ->patchJson("/shopping/{$this->plan->id}/stages/{$stage->id}", ['name' => '  Frutas '])
            ->assertOk()
            ->assertJsonPath('name', 'Frutas');

        $this->assertSame('Frutas', $stage->fresh()->name);
    }

    public function test_deleting_a_category_moves_its_items_to_another_one(): void
    {
        [$first, $second] = $this->twoStages();
        $item = $this->item($second, 'Leche');

        $this->actingAs($this->user)
            ->deleteJson("/shopping/{$this->plan->id}/stages/{$second->id}")
            ->assertOk()
            ->assertJsonPath('moved_to', $first->id);

        $this->assertNull(PlanStage::find($second->id));
        $this->assertSame($first->id, (int) $item->fresh()->stage_id);
    }

    public function test_the_last_category_cannot_be_deleted(): void
    {
        $this->plan->stages->skip(1)->each->delete();
        $only = $this->plan->stages()->first();

        $this->actingAs($this->user)
            ->deleteJson("/shopping/{$this->plan->id}/stages/{$only->id}")
            ->assertStatus(422);

        $this->assertNotNull($only->fresh());
    }

    public function test_an_item_moves_to_another_category_of_the_list(): void
    {
        [$first, $second] = $this->twoStages();
        $item = $this->item($first, 'Pan');

        $this->actingAs($this->user)
            ->patchJson("/shopping/{$this->plan->id}/items/{$item->id}/category", ['stage_id' => $second->id])
            ->assertOk()
            ->assertJsonStructure(['plan']);

        $this->assertSame($second->id, (int) $item->fresh()->stage_id);
    }

    public function test_another_team_cannot_touch_the_categories(): void
    {
        $stage = $this->plan->stages->first();
        $stranger = User::factory()->withPersonalTeam()->create();

        $this->actingAs($stranger)
            ->patchJson("/shopping/{$this->plan->id}/stages/{$stage->id}", ['name' => 'Mine'])
            ->assertForbidden();
        $this->actingAs($stranger)
            ->deleteJson("/shopping/{$this->plan->id}/stages/{$stage->id}")
            ->assertForbidden();
    }

    /**
     * @return array{0: PlanStage, 1: PlanStage}
     */
    private function twoStages(): array
    {
        $stages = $this->plan->stages()->orderBy('order')->get();
        if ($stages->count() < 2) {
            $stages->push($this->plan->stages()->create([
                'user_id' => $this->user->id,
                'team_id' => $this->plan->team_id,
                'name' => 'Lácteos',
                'order' => 99,
            ]));
        }

        return [$stages[0], $stages[1]];
    }

    private function item(PlanStage $stage, string $title): PlanItem
    {
        return PlanItem::create([
            'plan_id' => $this->plan->id,
            'team_id' => $this->plan->team_id,
            'user_id' => $this->user->id,
            'stage_id' => $stage->id,
            'title' => $title,
            'state' => PlanItem::STATE_PENDING,
            'order' => 1,
        ]);
    }
}

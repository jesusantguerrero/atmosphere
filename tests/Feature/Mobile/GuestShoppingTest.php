<?php

namespace Tests\Feature\Mobile;

use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
use App\Models\Team;
use App\Models\TeamMemberGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

class GuestShoppingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $guest;

    private Team $team;

    private LogerProfile $dianita;

    private Plan $babyList;

    private Plan $houseList;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->withPersonalTeam()->create();
        $this->team = $this->owner->ownedTeams()->first();
        $this->owner->forceFill(['current_team_id' => $this->team->id])->save();
        $this->guest = User::factory()->create();
        $this->team->users()->attach($this->guest, ['role' => TeamMemberGrant::GUEST_ROLE]);
        $this->guest->forceFill(['current_team_id' => $this->team->id])->save();
        $this->dianita = LogerProfile::create(['team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => 'Dianita']);
        $this->houseList = app(PlanService::class)->createPlanBoard($this->team, PlanTypes::SHOPPING_LIST, 'Súper');
        $this->babyList = app(PlanService::class)->createPlanBoard($this->team, PlanTypes::SHOPPING_LIST, 'Bebé');
        app(ProfileWorkspaceService::class)->link($this->dianita, $this->babyList, 'plan');
    }

    private function grant(string $access): void
    {
        TeamMemberGrant::create(['team_id' => $this->team->id, 'user_id' => $this->guest->id, 'loger_profile_id' => $this->dianita->id, 'access' => $access]);
    }

    private function item(Plan $plan, string $title): PlanItem
    {
        return PlanItem::create([
            'plan_id' => $plan->id, 'team_id' => $plan->team_id, 'user_id' => $this->owner->id,
            'stage_id' => $plan->stages()->first()->id, 'title' => $title, 'state' => PlanItem::STATE_PENDING, 'order' => 0,
        ]);
    }

    private function path(Plan $plan, string $suffix = ''): string
    {
        return '/api/mobile/shopping/'.$plan->id.$suffix;
    }

    public function test_a_guest_reads_only_her_linked_lists_and_never_gets_a_new_one(): void
    {
        $this->item($this->babyList, 'Pañales');
        Sanctum::actingAs($this->guest, ['*']);

        $this->getJson('/api/mobile/shopping')->assertForbidden();

        $this->grant(TeamMemberGrant::ACCESS_READ);
        $this->getJson('/api/mobile/shopping')->assertOk()
            ->assertJsonPath('activeListId', $this->babyList->id)
            ->assertJsonPath('plan.stages.0.items.0.title', 'Pañales')
            ->assertJsonCount(1, 'lists')
            ->assertJsonPath('lists.0.id', $this->babyList->id);
        $this->getJson('/api/mobile/shopping?plan='.$this->babyList->id)->assertOk();
        $this->getJson('/api/mobile/shopping?plan='.$this->houseList->id)->assertForbidden();

        app(ProfileWorkspaceService::class)->saveLinks($this->dianita, []);
        $countBefore = Plan::count();
        $this->getJson('/api/mobile/shopping')->assertOk()->assertJsonPath('plan', null)->assertJsonPath('lists', []);
        $this->assertSame($countBefore, Plan::count());
    }

    public function test_a_guest_with_read_cannot_change_items(): void
    {
        $item = $this->item($this->babyList, 'Pañales');
        $this->grant(TeamMemberGrant::ACCESS_READ);
        Sanctum::actingAs($this->guest, ['*']);

        $this->postJson($this->path($this->babyList, '/items'), ['title' => 'Toallitas'])->assertForbidden();
        $this->postJson($this->path($this->babyList, '/items/'.$item->id.'/cycle'))->assertForbidden();
    }

    public function test_a_guest_with_write_works_the_items_of_her_list(): void
    {
        $item = $this->item($this->babyList, 'Pañales');
        $this->grant(TeamMemberGrant::ACCESS_WRITE);
        Sanctum::actingAs($this->guest, ['*']);

        $added = $this->postJson($this->path($this->babyList, '/items'), ['title' => 'Toallitas'])->assertCreated()->json('id');
        $this->patchJson($this->path($this->babyList, '/items/'.$added), ['title' => 'Toallitas húmedas'])->assertOk();
        $this->postJson($this->path($this->babyList, '/items/'.$item->id.'/cycle'))->assertOk()->assertJsonPath('state', PlanItem::STATE_BUY);
        $this->postJson($this->path($this->babyList, '/check-all'))->assertOk();
        $this->postJson($this->path($this->babyList, '/reset'))->assertOk();
        $this->deleteJson($this->path($this->babyList, '/items/'.$added))->assertOk();

        $this->patchJson($this->path($this->babyList, '/items/'.$item->id), ['plan_id' => $this->houseList->id])->assertForbidden();
        $this->assertSame($this->babyList->id, (int) $item->fresh()->plan_id);
    }

    public function test_a_guest_cannot_manage_lists_or_finish_a_trip(): void
    {
        $houseItem = $this->item($this->houseList, 'Arroz');
        $this->grant(TeamMemberGrant::ACCESS_WRITE);
        Sanctum::actingAs($this->guest, ['*']);

        $this->postJson('/api/mobile/shopping/lists', ['name' => 'Mía'])->assertForbidden();
        $this->putJson($this->path($this->babyList), ['name' => 'Otra'])->assertForbidden();
        $this->deleteJson($this->path($this->babyList))->assertForbidden();
        $this->postJson($this->path($this->babyList, '/share'))->assertForbidden();
        $this->postJson($this->path($this->babyList, '/stages'), ['name' => 'Higiene'])->assertForbidden();
        $this->postJson($this->path($this->babyList, '/sessions/start'))->assertForbidden();
        $this->postJson($this->path($this->babyList, '/sessions/finish'), ['total' => 100])->assertForbidden();

        $this->postJson($this->path($this->houseList, '/items'), ['title' => 'Intruso'])->assertForbidden();
        $this->postJson($this->path($this->houseList, '/items/'.$houseItem->id.'/cycle'))->assertForbidden();
    }

    public function test_ticking_a_purchase_in_the_workspace_keeps_the_list_state_in_sync(): void
    {
        $item = $this->item($this->babyList, 'Pañales');
        $this->grant(TeamMemberGrant::ACCESS_WRITE);
        Sanctum::actingAs($this->guest, ['*']);
        $workspace = '/api/mobile/profiles/'.$this->dianita->id.'/workspace/items/'.$item->id;

        $this->patchJson($workspace, ['done' => true])->assertOk()->assertJsonPath('workspace.purchases.0.done', true);
        $this->assertSame(PlanItem::STATE_BUY, $item->fresh()->state);
        $this->patchJson($workspace, ['done' => false])->assertOk();
        $this->assertSame(PlanItem::STATE_PENDING, $item->fresh()->state);
        $this->getJson('/api/mobile/shopping')->assertJsonPath('plan.stages.0.items.0.state', PlanItem::STATE_PENDING);
    }
}

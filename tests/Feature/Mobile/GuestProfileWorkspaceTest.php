<?php

namespace Tests\Feature\Mobile;

use App\Domains\Housing\Models\Occurrence;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
use App\Models\Team;
use App\Models\TeamMemberGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Plan\Entities\PlanItem;
use Tests\TestCase;

class GuestProfileWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $guest;

    private Team $team;

    private LogerProfile $dianita;

    private LogerProfile $house;

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
        $this->house = LogerProfile::create(['team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => 'Casa']);
    }

    private function grant(LogerProfile $profile, string $access): void
    {
        TeamMemberGrant::create(['team_id' => $this->team->id, 'user_id' => $this->guest->id, 'loger_profile_id' => $profile->id, 'access' => $access]);
    }

    /**
     * @return array{0: string, 1: PlanItem}
     */
    private function boardWithPage(LogerProfile $profile): array
    {
        $workspace = app(ProfileWorkspaceService::class);
        $board = $workspace->createBoard($profile, ['name' => 'Escuela', 'view' => 'notebook'], $this->owner->id);
        $page = $workspace->createPage($profile, $board, $board->stages()->first(), ['title' => 'Llevar merienda'], $this->owner->id);

        return [(string) $board->id, $page];
    }

    private function path(LogerProfile $profile, string $suffix = ''): string
    {
        return '/api/mobile/profiles/'.$profile->id.'/workspace'.$suffix;
    }

    public function test_the_owner_lists_creates_and_reads_profiles_with_colors(): void
    {
        Sanctum::actingAs($this->owner, ['*']);

        $this->getJson('/api/mobile/profiles')->assertOk()
            ->assertJsonPath('profiles.0', ['id' => $this->house->id, 'name' => 'Casa', 'color' => LogerProfile::defaultColor((string) $this->house->id)])
            ->assertJsonPath('profiles.1.name', 'Dianita');

        $created = $this->postJson('/api/mobile/profiles', ['name' => 'Toby', 'color' => '#AABBCC'])->assertCreated()->json('profile');
        $this->assertSame(['name' => 'Toby', 'color' => '#AABBCC'], ['name' => $created['name'], 'color' => $created['color']]);
        $this->postJson('/api/mobile/profiles', ['name' => '', 'color' => 'red'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'color']);

        [$boardId] = $this->boardWithPage($this->dianita);
        $this->getJson($this->path($this->dianita))->assertOk()
            ->assertJsonPath('profile.name', 'Dianita')
            ->assertJsonPath('profile.color', $this->dianita->color)
            ->assertJsonPath('workspace.boards.0.id', $boardId)
            ->assertJsonPath('workspace.boards.0.stages.0.items.0.title', 'Llevar merienda');
        $this->assertNotEmpty($this->getJson($this->path($this->dianita))->json('workspace.availableLinks'));
    }

    public function test_the_owner_writes_through_the_mobile_mirror(): void
    {
        Sanctum::actingAs($this->owner, ['*']);

        $created = $this->postJson($this->path($this->dianita, '/boards'), ['name' => 'Salud', 'view' => 'list'])->assertOk();
        $boardId = $created->json('board_id');
        $stageId = $created->json('workspace.boards.0.stages.0.id');
        $notebookId = $this->postJson($this->path($this->dianita, '/boards/'.$boardId.'/notebooks'), ['name' => 'Vacunas'])->assertOk()->json('notebook_id');
        $this->patchJson($this->path($this->dianita, '/boards/'.$boardId.'/notebooks/'.$notebookId), ['name' => 'Vacunas 2027'])->assertOk();
        $itemId = $this->postJson($this->path($this->dianita, '/boards/'.$boardId.'/pages'), ['stage_id' => $stageId, 'title' => ''])->assertOk()->json('item_id');
        $this->patchJson($this->path($this->dianita, '/items/'.$itemId), ['kind' => 'event', 'when' => '2026-11-20T09:30'])->assertOk()
            ->assertJsonPath('workspace.boards.0.stages.0.items.0.title', 'Sin título')
            ->assertJsonPath('workspace.boards.0.stages.0.items.0.when', '2026-11-20T09:30');
        $this->patchJson($this->path($this->dianita, '/boards/'.$boardId), ['view' => 'kanban'])->assertOk()
            ->assertJsonPath('workspace.boards.0.view', 'kanban');

        $this->putJson('/api/mobile/profile-task-links/'.$itemId, ['profile_ids' => [$this->house->id]])->assertOk()
            ->assertJsonPath('profile_ids', [$this->house->id]);
    }

    public function test_a_guest_sees_only_granted_profiles_and_no_linkable_household(): void
    {
        $this->grant($this->dianita, TeamMemberGrant::ACCESS_READ);
        [$boardId] = $this->boardWithPage($this->dianita);
        $this->boardWithPage($this->house);
        Sanctum::actingAs($this->guest, ['*']);

        $this->getJson('/api/mobile/profiles')->assertOk()->assertJsonCount(1, 'profiles')->assertJsonPath('profiles.0.id', $this->dianita->id);
        $this->getJson($this->path($this->dianita))->assertOk()
            ->assertJsonPath('workspace.boards.0.id', $boardId)
            ->assertJsonPath('workspace.availableLinks', []);
        $this->getJson($this->path($this->house))->assertForbidden();
        $this->postJson('/api/mobile/profiles', ['name' => 'Otro'])->assertForbidden();
    }

    public function test_a_guest_with_write_only_ticks_pages_and_occurrences(): void
    {
        $this->grant($this->dianita, TeamMemberGrant::ACCESS_WRITE);
        [$boardId, $page] = $this->boardWithPage($this->dianita);
        $occurrence = Occurrence::create(['team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => 'Baño']);
        app(ProfileWorkspaceService::class)->link($this->dianita, $occurrence, 'occurrence');
        [, $housePage] = $this->boardWithPage($this->house);
        Sanctum::actingAs($this->guest, ['*']);

        $this->patchJson($this->path($this->dianita, '/items/'.$page->id), ['done' => true, 'title' => 'Merienda lista', 'body' => 'Fruta', 'when' => '2026-11-18T08:00'])->assertOk()
            ->assertJsonPath('workspace.boards.0.stages.0.items.0.done', true)
            ->assertJsonPath('workspace.boards.0.stages.0.items.0.title', 'Merienda lista');
        $this->patchJson($this->path($this->dianita, '/items/'.$page->id), ['kind' => 'note'])->assertForbidden();
        $this->patchJson($this->path($this->dianita, '/occurrences/'.$occurrence->id), ['checked' => true])->assertOk()
            ->assertJsonPath('workspace.occurrences.0.checked', true);

        $this->patchJson($this->path($this->dianita, '/items/'.$housePage->id), ['done' => true])->assertNotFound();
        $this->patchJson($this->path($this->house, '/items/'.$housePage->id), ['done' => true])->assertForbidden();
        $this->postJson($this->path($this->dianita, '/boards'), ['name' => 'Nuevo', 'view' => 'list'])->assertForbidden();
        $this->patchJson($this->path($this->dianita, '/boards/'.$boardId), ['view' => 'kanban'])->assertForbidden();
        $this->postJson($this->path($this->dianita, '/boards/'.$boardId.'/pages'), ['stage_id' => 1, 'title' => 'x'])->assertForbidden();
        $this->putJson($this->path($this->dianita, '/links'), ['selections' => []])->assertForbidden();
        $this->putJson('/api/mobile/profile-task-links/'.$page->id, ['profile_ids' => []])->assertForbidden();
    }

    public function test_a_guest_with_read_cannot_write_and_reads_task_links_of_her_profiles_only(): void
    {
        $this->grant($this->dianita, TeamMemberGrant::ACCESS_READ);
        [, $page] = $this->boardWithPage($this->dianita);
        [, $housePage] = $this->boardWithPage($this->house);
        app(ProfileWorkspaceService::class)->setTaskProfiles($page, [$this->dianita->id, $this->house->id]);
        Sanctum::actingAs($this->guest, ['*']);

        $this->patchJson($this->path($this->dianita, '/items/'.$page->id), ['done' => true])->assertForbidden();
        $this->getJson('/api/mobile/profile-task-links/'.$page->id)->assertOk()
            ->assertJsonPath('profiles', [['id' => $this->dianita->id, 'name' => 'Dianita']])
            ->assertJsonPath('profile_ids', [$this->dianita->id]);
        $this->getJson('/api/mobile/profile-task-links/'.$housePage->id)->assertNotFound();
    }
}

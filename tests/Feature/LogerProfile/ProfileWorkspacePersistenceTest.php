<?php

namespace Tests\Feature\LogerProfile;

use App\Domains\Housing\Models\Occurrence;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionLine;
use App\Models\User;
use Database\Seeders\ProfileWorkspaceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Modules\Plan\Entities\PlanItem;
use Tests\TestCase;

class ProfileWorkspacePersistenceTest extends TestCase
{
    use RefreshDatabase;

    private LogerProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->app->instance('env', 'local');
        $this->actingAs((new User)->forceFill(['id' => 980001, 'current_team_id' => 980001]));
        $this->profile = LogerProfile::create(['team_id' => 980001, 'user_id' => 980001, 'name' => 'Workspace test']);
    }

    private function path(string $suffix): string
    {
        return '/loger-profiles/'.$this->profile->id.'/workspace'.$suffix;
    }

    public function test_pages_fields_views_and_completion_survive_a_fresh_read(): void
    {
        $response = $this->postJson($this->path('/boards'), ['name' => 'Notebook', 'view' => 'notebook'])->assertOk();
        $boardId = $response->json('board_id');
        $stageId = $response->json('workspace.boards.0.stages.0.id');
        $itemId = $this->postJson($this->path('/boards/'.$boardId.'/pages'), ['stage_id' => $stageId, 'title' => 'First page'])->assertOk()->json('item_id');
        $this->patchJson($this->path('/items/'.$itemId), ['title' => 'Saved title', 'body' => 'Persisted content', 'kind' => 'event', 'when' => '2026-10-21T16:00', 'done' => true])->assertOk();
        $this->patchJson($this->path('/boards/'.$boardId), ['view' => 'kanban'])->assertOk();
        $workspace = app(ProfileWorkspaceService::class)->snapshot($this->profile->fresh());
        $this->assertSame('kanban', $workspace['boards'][0]['view']);
        $page = $workspace['boards'][0]['stages'][0]['items'][0];
        $this->assertSame('Saved title', $page['title']);
        $this->assertSame('Persisted content', $page['body']);
        $this->assertSame('event', $page['kind']);
        $this->assertSame('2026-10-21T16:00', $page['when']);
        $this->assertTrue($page['done']);
        $this->assertDatabaseHas('plan_items', ['id' => $itemId, 'state' => PlanItem::STATE_BUY, 'profile_id' => $this->profile->id, 'rrule' => null]);
    }

    public function test_pages_can_be_created_with_empty_title(): void
    {
        $response = $this->postJson($this->path('/boards'), ['name' => 'Notebook', 'view' => 'notebook'])->assertOk();
        $boardId = $response->json('board_id');
        $stageId = $response->json('workspace.boards.0.stages.0.id');
        $this->postJson($this->path('/boards/'.$boardId.'/pages'), ['stage_id' => $stageId, 'title' => ''])->assertOk();
        $workspace = app(ProfileWorkspaceService::class)->snapshot($this->profile->fresh());
        $this->assertSame('Sin título', $workspace['boards'][0]['stages'][0]['items'][0]['title']);
    }

    public function test_validation_and_team_boundaries_reject_changes(): void
    {
        $this->postJson($this->path('/boards'), ['name' => '', 'view' => 'invalid'])->assertUnprocessable();
        $other = LogerProfile::create(['team_id' => 980002, 'user_id' => 980002, 'name' => 'Foreign']);
        $board = app(ProfileWorkspaceService::class)->createBoard($other, ['name' => 'Private', 'view' => 'list'], 980002);
        $this->patchJson($this->path('/boards/'.$board->id), ['view' => 'kanban'])->assertNotFound();
        $this->putJson($this->path('/links'), ['selections' => [['type' => 'plan', 'id' => $board->id]]])->assertNotFound();
        $this->get('/loger-profiles/'.$other->id.'/preview')->assertNotFound();
        $this->assertSame(3, $board->fresh()->last_view_id);
    }

    public function test_demo_is_separate_idempotent_and_links_existing_models(): void
    {
        $seeder = app(ProfileWorkspaceDemoSeeder::class);
        $demo = $seeder->seedFor($this->profile);
        $snapshot = app(ProfileWorkspaceService::class)->snapshot($demo);
        $this->assertNotSame($this->profile->id, $demo->id);
        $this->assertCount(3, $snapshot['boards']);
        $this->assertCount(3, $snapshot['categories']);
        $this->assertCount(3, $snapshot['purchases']);
        $this->assertCount(2, $snapshot['occurrences']);
        $this->assertCount(0, $snapshot['transactions']);
        $this->assertEquals(0, $snapshot['categories']->sum('available'));
        $itemCount = PlanItem::count();
        $this->assertSame($demo->id, $seeder->seedFor($this->profile)->id);
        $this->assertSame($itemCount, PlanItem::count());
        $this->assertCount(0, app(ProfileWorkspaceService::class)->links($this->profile));
    }

    public function test_occurrence_registration_is_idempotent_and_preserves_history(): void
    {
        $occurrence = Occurrence::withoutEvents(fn () => Occurrence::create(['team_id' => 980001, 'user_id' => 980001, 'name' => 'Milk', 'last_date' => '2026-10-01', 'log' => []]));
        app(ProfileWorkspaceService::class)->link($this->profile, $occurrence, 'occurrence');
        $this->patchJson($this->path('/occurrences/'.$occurrence->id), ['checked' => true])->assertOk();
        $this->patchJson($this->path('/occurrences/'.$occurrence->id), ['checked' => true])->assertOk();
        $this->assertCount(2, $occurrence->fresh()->log);
        $this->patchJson($this->path('/occurrences/'.$occurrence->id), ['checked' => false])->assertOk();
        $this->assertSame(['2026-10-01'], $occurrence->fresh()->log);
    }

    public function test_category_group_links_include_descendant_transactions_and_unlink_preserves_records(): void
    {
        $demo = app(ProfileWorkspaceDemoSeeder::class)->seedFor($this->profile);
        $service = app(ProfileWorkspaceService::class);
        $snapshot = $service->snapshot($demo);
        $categoryId = $snapshot['categories'][0]['id'];
        $transaction = (new Transaction)->forceFill(['team_id' => $demo->team_id, 'user_id' => $demo->user_id, 'category_id' => $categoryId, 'account_id' => 1001, 'currency_code' => 'DOP', 'date' => '2026-04-19', 'description' => 'School materials', 'direction' => 'credit', 'status' => Transaction::STATUS_VERIFIED, 'total' => 25]);
        $transaction->save();
        $line = (new TransactionLine)->forceFill(['transaction_id' => $transaction->id, 'team_id' => $demo->team_id, 'user_id' => $demo->user_id, 'category_id' => $categoryId, 'account_id' => 1001, 'date' => '2026-04-19', 'concept' => 'Materials', 'index' => 0, 'anchor' => 1, 'type' => -1, 'amount' => 25]);
        $line->save();
        $transactions = $service->snapshot($demo)['transactions'];
        $this->assertCount(1, $transactions);
        $this->assertSame('School materials', $transactions[0]['name']);
        $this->assertSame(-25.0, $transactions[0]['amount']);
        $options = $service->snapshot($demo)['availableLinks'];
        $selections = collect($options)->where('linked', true)->map(fn ($option) => ['type' => $option['type'], 'id' => $option['id']])->values()->all();
        $this->putJson('/loger-profiles/'.$demo->id.'/workspace/links', ['selections' => $selections])->assertOk();
        $this->putJson('/loger-profiles/'.$demo->id.'/workspace/links', ['selections' => []])->assertOk();
        $this->assertCount(0, $service->links($demo));
        $this->assertDatabaseHas('transaction_lines', ['id' => $line->id, 'category_id' => $categoryId]);
        $this->assertDatabaseHas('categories', ['id' => $categoryId]);
        $this->assertDatabaseHas('plans', ['id' => $snapshot['boards'][0]['id']]);
    }

    public function test_notebooks_persist_under_their_folder_and_pages_stay_in_their_notebook(): void
    {
        $folder = $this->postJson($this->path('/boards'), ['name' => 'Salud', 'view' => 'notebook'])->assertOk()->json('board_id');
        $response = $this->postJson($this->path('/boards/'.$folder.'/notebooks'), ['name' => 'Test de articulación a la repetición'])->assertOk();
        $notebook = $response->json('notebook_id');
        $page = $this->postJson($this->path('/boards/'.$folder.'/pages'), ['stage_id' => $notebook, 'title' => 'Evaluación inicial'])->assertOk()->json('item_id');
        $snapshot = app(ProfileWorkspaceService::class)->snapshot($this->profile->fresh());
        $stages = $snapshot['boards'][0]['stages'];
        $this->assertCount(2, $stages);
        $this->assertCount(0, $stages[0]['items']);
        $this->assertSame('Test de articulación a la repetición', $stages[1]['name']);
        $this->assertSame($page, $stages[1]['items'][0]['id']);
        $this->assertDatabaseHas('plan_stages', ['id' => $notebook, 'plan_id' => $folder, 'name' => 'Test de articulación a la repetición']);
        $this->postJson($this->path('/boards/'.$folder.'/notebooks'), ['name' => ''])->assertUnprocessable();
        $other = LogerProfile::create(['team_id' => 980002, 'user_id' => 980002, 'name' => 'Other family']);
        $otherFolder = app(ProfileWorkspaceService::class)->createBoard($other, ['name' => 'Private', 'view' => 'notebook'], 980002);
        $this->postJson($this->path('/boards/'.$otherFolder->id.'/notebooks'), ['name' => 'Not allowed'])->assertNotFound();
        $anotherFolder = $this->postJson($this->path('/boards'), ['name' => 'Colegio', 'view' => 'notebook'])->assertOk()->json('board_id');
        $this->postJson($this->path('/boards/'.$anotherFolder.'/pages'), ['stage_id' => $notebook, 'title' => 'Wrong folder'])->assertNotFound();
    }

    public function test_pillar_overview_lists_current_team_pending_tasks_and_upcoming_events(): void
    {
        $demo = app(ProfileWorkspaceDemoSeeder::class)->seedFor($this->profile);
        LogerProfile::create(['team_id' => 980002, 'user_id' => 980002, 'name' => 'Other family']);
        $summaries = app(ProfileWorkspaceService::class)->overview(980001);
        $this->assertCount(2, $summaries);
        $summary = collect($summaries)->firstWhere('id', $demo->id);
        $this->assertCount(2, $summary['pending']);
        $this->assertCount(2, $summary['upcoming']);
        $this->assertSame('/loger-profiles/'.$demo->id, $summary['url']);
        $this->get('/loger-profiles/overview')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('LogerProfile/Overview')->has('profiles', 3));
        $this->assertFalse(collect($summaries)->contains('name', 'Other family'));
    }

    public function test_workspace_and_writes_are_available_in_production_and_team_scoped(): void
    {
        $this->app->instance('env', 'production');
        $this->get('/loger-profiles/'.$this->profile->id)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('LogerProfile/WorkspacePreview')->where('profile.id', $this->profile->id));
        $this->postJson($this->path('/boards'), ['name' => 'Production folder', 'view' => 'notebook'])->assertOk();
        $foreign = LogerProfile::create(['team_id' => 980002, 'user_id' => 980002, 'name' => 'Foreign']);
        $this->get('/loger-profiles/'.$foreign->id)->assertNotFound();
        $this->assertDatabaseHas('plans', ['team_id' => $this->profile->team_id, 'name' => 'Production folder']);
    }
}

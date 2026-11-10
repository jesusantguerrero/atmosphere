<?php

namespace Tests\Feature;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Actions\GmailReceived;
use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Services\UniversalBankAutomationService;
use App\Domains\Transaction\Models\Transaction;
use App\Models\ProcessedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class BankEmailSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $teamId;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-09 10:00:00');
        Cache::flush();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->teamId = $this->user->ownedTeams()->first()->id;
        $this->user->forceFill(['current_team_id' => $this->teamId])->save();
        $this->user = $this->user->fresh();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sync_starts_a_few_days_before_the_last_imported_movement(): void
    {
        $automation = $this->automation();
        $this->assertSame('2026-10-10', UniversalBankAutomationService::syncSince($this->teamId, $automation->id));

        $this->importedEmail($automation, '2026-11-05', 'gmail-1');
        $this->importedEmail($automation, '2026-10-20', 'gmail-2');

        $this->assertSame('2026-11-05', UniversalBankAutomationService::lastImportDate($this->teamId, $automation->id));
        $this->assertSame('2026-11-02', UniversalBankAutomationService::syncSince($this->teamId, $automation->id));
    }

    public function test_each_poll_moves_the_sync_one_step_until_the_last_page(): void
    {
        $automation = $this->automation();
        $this->gmail();
        $steps = $this->fakeSteps([
            ['checked' => 10, 'next_page_token' => 'page-2', 'done' => false, 'import' => 'gmail-a'],
            ['checked' => 4, 'next_page_token' => null, 'done' => true, 'import' => 'gmail-b'],
        ], $automation);

        // The step budget is long enough for one page per request here.
        $steps->shouldReceive('syncStep')->with(Mockery::any(), '2026-10-10', null, null)->once()->andReturnUsing($this->stepAnswer(0));
        $this->actingAs($this->user)->postJson('/integrations/bank-transactions/sync')
            ->assertStatus(202)
            ->assertJsonPath('sync.status', 'done')
            ->assertJsonPath('sync.checked', 14)
            ->assertJsonPath('sync.imported', 2)
            ->assertJsonPath('sync.since', '2026-10-10')
            ->assertJsonMissingPath('sync.page_token');
    }

    public function test_a_sync_left_without_progress_shows_as_stalled_and_can_start_again(): void
    {
        $this->automation();
        $this->gmail();
        UniversalBankAutomationService::rememberSync($this->teamId, ['status' => 'running', 'since' => '2026-10-10', 'checked' => 30, 'imported' => 0, 'page_token' => 'p', 'latest' => null, 'processed_before' => 0]);

        $this->travel(3)->minutes();

        $this->actingAs($this->user)->getJson('/integrations/bank-transactions')
            ->assertOk()
            ->assertJsonPath('sync.status', 'stalled')
            ->assertJsonPath('sync.checked', 30);
        $this->assertFalse(UniversalBankAutomationService::syncIsRunning($this->teamId));
    }

    public function test_sync_needs_gmail_and_the_bank_import_on(): void
    {
        $this->actingAs($this->user)->postJson('/integrations/bank-transactions/sync')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sync');

        $this->assertNull(UniversalBankAutomationService::presentSync($this->teamId));
    }

    public function test_a_failed_step_is_reported_and_mobile_sees_it(): void
    {
        $automation = $this->automation();
        $this->gmail();
        $this->mock(GmailReceived::class)->shouldReceive('syncStep')->andThrow(new \RuntimeException('Token expired'));
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mobile/bank-email/sync')->assertStatus(202)
            ->assertJsonPath('sync.status', 'failed')
            ->assertJsonPath('sync.error', 'Token expired');
        $this->getJson('/api/mobile/bank-email')->assertOk()->assertJsonPath('sync.status', 'failed');
        $this->assertNull($automation->fresh()->track);
    }

    /**
     * @param  list<array{checked: int, next_page_token: ?string, done: bool, import: string}>  $answers
     */
    private function fakeSteps(array $answers, Automation $automation): Mockery\MockInterface
    {
        $this->answers = $answers;
        $this->syncAutomation = $automation;
        $mock = $this->mock(GmailReceived::class);
        $mock->shouldReceive('syncStep')->with(Mockery::any(), '2026-10-10', 'page-2', Mockery::any())->andReturnUsing($this->stepAnswer(1));

        return $mock;
    }

    /** @var list<array{checked: int, next_page_token: ?string, done: bool, import: string}> */
    private array $answers = [];

    private ?Automation $syncAutomation = null;

    private function stepAnswer(int $index): \Closure
    {
        return function () use ($index) {
            $answer = $this->answers[$index];
            $this->importedEmail($this->syncAutomation, '2026-11-0'.($index + 1), $answer['import']);

            return ['checked' => $answer['checked'], 'processed' => 1, 'next_page_token' => $answer['next_page_token'], 'done' => $answer['done'], 'latest' => ['historyId' => $index + 1]];
        };
    }

    private function automation(): Automation
    {
        return Automation::forceCreate([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'name' => UniversalBankAutomationService::NAME,
            'status' => true,
            'is_background' => true,
        ]);
    }

    private function gmail(): Integration
    {
        return Integration::forceCreate([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'name' => 'Gmail',
            'token' => 'token',
        ]);
    }

    private function importedEmail(Automation $automation, string $date, string $gmailId): void
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'date' => $date,
            'description' => 'Compra',
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => 100,
            'currency_code' => 'DOP',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        ProcessedEmail::query()->forceCreate([
            'team_id' => $this->teamId,
            'gmail_id' => $gmailId,
            'automation_id' => $automation->id,
            'transaction_id' => $transactionId,
        ]);
    }
}

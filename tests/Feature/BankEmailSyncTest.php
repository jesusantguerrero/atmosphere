<?php

namespace Tests\Feature;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Services\UniversalBankAutomationService;
use App\Domains\Transaction\Models\Transaction;
use App\Jobs\SyncBankEmails;
use App\Models\ProcessedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
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

    public function test_sync_now_runs_after_the_response_and_reports_running(): void
    {
        Bus::fake();
        $this->automation();
        $this->gmail();

        $this->actingAs($this->user)->postJson('/integrations/bank-transactions/sync')
            ->assertStatus(202)
            ->assertJsonPath('sync.status', 'running')
            ->assertJsonPath('sync.since', '2026-10-10');

        Bus::assertDispatchedAfterResponse(SyncBankEmails::class, fn (SyncBankEmails $job) => $job->teamId === $this->teamId);

        // A second press while it runs does not start another sync.
        $this->actingAs($this->user)->postJson('/integrations/bank-transactions/sync')->assertStatus(202);
        Bus::assertDispatchedAfterResponseTimes(SyncBankEmails::class, 1);

        $this->actingAs($this->user)->getJson('/integrations/bank-transactions')->assertOk()->assertJsonPath('sync.status', 'running');
    }

    public function test_sync_needs_gmail_and_the_bank_import_on(): void
    {
        Bus::fake();

        $this->actingAs($this->user)->postJson('/integrations/bank-transactions/sync')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sync');

        Bus::assertNothingDispatched();
    }

    public function test_mobile_can_read_the_status_and_start_a_sync(): void
    {
        Bus::fake();
        $this->automation();
        $this->gmail();
        Sanctum::actingAs($this->user);

        $this->getJson('/api/mobile/bank-email')->assertOk()->assertJsonPath('connected', true)->assertJsonPath('sync', null);
        $this->postJson('/api/mobile/bank-email/sync')->assertStatus(202)->assertJsonPath('sync.status', 'running');
    }

    public function test_a_failed_sync_is_reported_on_the_status(): void
    {
        $automation = $this->automation();
        // No Gmail integration: the sync cannot connect.

        (new SyncBankEmails($this->user->id, $this->teamId))->handle();

        $sync = UniversalBankAutomationService::status($this->user)['sync'];
        $this->assertSame('failed', $sync['status']);
        $this->assertSame('2026-10-10', $sync['since']);
        $this->assertNotEmpty($sync['error']);
        $this->assertNull($automation->fresh()->track);
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

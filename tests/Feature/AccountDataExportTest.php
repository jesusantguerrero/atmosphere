<?php

namespace Tests\Feature;

use App\Domains\AppCore\Services\AccountDataExporter;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Jobs\ExportAccountData;
use App\Models\Account;
use App\Models\User;
use App\Notifications\AccountDataExportReady;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Insane\Journal\Models\Core\AccountDetailType;
use Tests\TestCase;
use ZipArchive;

class AccountDataExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();
        Storage::fake('local');
    }

    private function userWithAccount(string $accountName = 'Checking'): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();

        Account::create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => $accountName,
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::BANK)->value('id'),
        ]);

        return $user;
    }

    /**
     * @return array<string, string>
     */
    private function zipContents(string $path): array
    {
        $zip = new ZipArchive;
        $zip->open(Storage::disk('local')->path($path));
        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $files[$name] = $zip->getFromName($name);
        }
        $zip->close();

        return $files;
    }

    public function test_requesting_an_export_queues_it(): void
    {
        Queue::fake();
        $user = $this->userWithAccount();

        $this->actingAs($user)->post('/user/data-export')->assertRedirect();

        Queue::assertPushed(ExportAccountData::class, fn (ExportAccountData $job) => $job->user->is($user));
    }

    public function test_the_archive_holds_the_profile_and_the_team_tables_without_secrets(): void
    {
        $user = $this->userWithAccount();
        DB::table('integrations')->insert([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => 'Gmail',
            'token' => 'super-secret-token',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $files = $this->zipContents(app(AccountDataExporter::class)->export($user));
        $folder = str()->slug($user->currentTeam->name).'-'.$user->current_team_id;

        $this->assertArrayHasKey('README.txt', $files);
        $this->assertSame($user->email, json_decode($files['profile.json'], true)['email']);
        $this->assertStringContainsString('Checking', $files["{$folder}/accounts.csv"]);
        $this->assertArrayNotHasKey("{$folder}/integrations.csv", $files);
        $this->assertStringNotContainsString('super-secret-token', implode('', $files));
        $this->assertStringNotContainsString('password', $files['profile.json']);
    }

    public function test_the_archive_leaves_out_other_households(): void
    {
        $user = $this->userWithAccount();
        $this->userWithAccount('Someone else savings');

        $files = $this->zipContents(app(AccountDataExporter::class)->export($user));

        $this->assertStringNotContainsString('Someone else savings', implode('', $files));
    }

    public function test_the_job_notifies_with_a_link_only_its_owner_can_download(): void
    {
        Notification::fake();
        $user = $this->userWithAccount();

        ExportAccountData::dispatchSync($user);

        $exportId = null;
        Notification::assertSentTo($user, AccountDataExportReady::class, function (AccountDataExportReady $notification) use (&$exportId) {
            $exportId = $notification->exportId;

            return true;
        });

        $this->actingAs($user)->get("/user/data-export/{$exportId}")->assertOk()->assertDownload();

        $other = $this->userWithAccount();
        $this->actingAs($other)->get("/user/data-export/{$exportId}")->assertNotFound();
    }

    public function test_old_exports_are_pruned(): void
    {
        $user = $this->userWithAccount();
        $path = app(AccountDataExporter::class)->export($user);
        touch(Storage::disk('local')->path($path), now()->subDays(AccountDataExporter::DAYS_KEPT + 1)->getTimestamp());
        $fresh = app(AccountDataExporter::class)->export($user);

        $this->artisan('loger:prune-data-exports')->assertSuccessful();

        Storage::disk('local')->assertMissing($path);
        Storage::disk('local')->assertExists($fresh);
    }
}

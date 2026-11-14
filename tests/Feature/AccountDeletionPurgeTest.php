<?php

namespace Tests\Feature;

use App\Domains\AppCore\Services\TeamDataPurger;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Models\Account;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\AccountDetailType;
use Tests\TestCase;

/**
 * Deleting an account used to delete the team row and soft-delete a handful of
 * tables, leaving the rest of the household's data behind.
 */
class AccountDeletionPurgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();
    }

    private function userWithData(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();

        $bank = Account::create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => 'Checking',
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::BANK)->value('id'),
        ]);

        $transactionId = DB::table('transactions')->insertGetId([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'account_id' => $bank->id,
            'date' => '2026-11-01',
            'description' => 'Salary',
            'direction' => 'DEPOSIT',
            'total' => 1000,
            'currency_code' => 'DOP',
            'status' => 'verified',
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transaction_lines')->insert([
            'transaction_id' => $transactionId,
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'account_id' => $bank->id,
            'date' => '2026-11-01',
            'type' => 1,
            'amount' => 1000,
            'anchor' => 1,
            'index' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $labelId = DB::table('labels')->insertGetId([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => 'School',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('labelables')->insert([
            'label_id' => $labelId,
            'labelable_id' => $transactionId,
            'labelable_type' => 'transaction',
        ]);

        return $user;
    }

    /**
     * @return array<string, int>
     */
    private function rowsLeftFor(int $teamId): array
    {
        return collect(app(TeamDataPurger::class)->teamTables())
            ->mapWithKeys(fn (string $table) => [$table => DB::table($table)->where('team_id', $teamId)->count()])
            ->filter()
            ->all();
    }

    public function test_deleting_an_account_removes_every_row_of_its_teams(): void
    {
        $user = $this->userWithData();
        $teamId = $user->current_team_id;

        $this->assertNotEmpty($this->rowsLeftFor($teamId));

        $this->actingAs($user)->delete('/user', ['password' => 'password']);

        $this->assertNull($user->fresh());
        $this->assertNull(Team::find($teamId));
        $this->assertSame([], $this->rowsLeftFor($teamId));
        $this->assertSame(0, DB::table('labelables')->count());
    }

    public function test_purging_one_team_leaves_other_teams_alone(): void
    {
        $user = $this->userWithData();
        $other = $this->userWithData();

        app(TeamDataPurger::class)->purge($user->current_team_id);

        $this->assertSame([], $this->rowsLeftFor($user->current_team_id));
        $this->assertSame(1, DB::table('transactions')->where('team_id', $other->current_team_id)->count());
        $this->assertSame(1, DB::table('labels')->where('team_id', $other->current_team_id)->count());
        $this->assertSame(1, DB::table('labelables')->count());
    }

    public function test_a_shared_team_passes_to_a_member_instead_of_being_deleted(): void
    {
        $user = $this->userWithData();
        $team = $user->currentTeam;
        $team->users()->attach($editor = User::factory()->create(), ['role' => 'editor']);
        $team->users()->attach($admin = User::factory()->create(), ['role' => 'admin']);

        $this->actingAs($user)->delete('/user', ['password' => 'password']);

        $team->refresh();
        $this->assertNull($user->fresh());
        $this->assertSame($admin->id, $team->user_id);
        $this->assertFalse($team->personal_team);
        $this->assertSame([$editor->id], $team->users()->pluck('users.id')->all());
        $this->assertSame(1, DB::table('transactions')->where('team_id', $team->id)->count());
    }

    public function test_deleting_an_account_drops_its_devices_and_notifications(): void
    {
        $user = $this->userWithData();

        DB::table('user_devices')->insert([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'device_id' => 'device-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('notifications')->insert([
            'id' => (string) str()->uuid(),
            'type' => 'test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->delete('/user', ['password' => 'password']);

        $this->assertSame(0, DB::table('user_devices')->where('user_id', $user->id)->count());
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $user->id)->count());
    }
}

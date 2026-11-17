<?php

namespace Tests\Feature\Mobile;

use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
use App\Models\Account;
use App\Models\Team;
use App\Models\TeamMemberGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfilesOverviewTest extends TestCase
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

        $workspace = app(ProfileWorkspaceService::class);
        $board = $workspace->createBoard($this->dianita, ['name' => 'Escuela', 'view' => 'notebook'], $this->owner->id);
        $workspace->createPage($this->dianita, $board, $board->stages()->first(), ['title' => 'Comprar uniforme', 'kind' => 'task'], $this->owner->id);
        $workspace->createPage($this->dianita, $board, $board->stages()->first(), ['title' => 'Acto escolar', 'kind' => 'event', 'when' => now()->addDays(3)->format('Y-m-d\TH:i')], $this->owner->id);
    }

    private function account(string $name): Account
    {
        return Account::create(['team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => $name, 'currency_code' => 'DOP']);
    }

    public function test_the_owner_gets_every_profile_with_its_pending_and_upcoming(): void
    {
        Sanctum::actingAs($this->owner, ['*']);

        $profiles = $this->getJson('/api/mobile/profiles/overview')->assertOk()->json('profiles');

        $this->assertSame(['Casa', 'Dianita'], array_column($profiles, 'name'));
        $dianita = $profiles[1];
        $this->assertSame($this->dianita->color, $dianita['color']);
        $this->assertSame(['Comprar uniforme'], array_column($dianita['pending'], 'title'));
        $this->assertSame(['Acto escolar'], array_column($dianita['upcoming'], 'title'));
        $this->assertSame([], $profiles[0]['pending']);
    }

    public function test_a_guest_only_gets_her_granted_profiles(): void
    {
        Sanctum::actingAs($this->guest, ['*']);
        $this->getJson('/api/mobile/profiles/overview')->assertForbidden();

        TeamMemberGrant::create(['team_id' => $this->team->id, 'user_id' => $this->guest->id, 'loger_profile_id' => $this->dianita->id, 'access' => 'read']);
        $this->getJson('/api/mobile/profiles/overview')->assertOk()
            ->assertJsonCount(1, 'profiles')
            ->assertJsonPath('profiles.0.id', $this->dianita->id)
            ->assertJsonPath('profiles.0.pending.0.title', 'Comprar uniforme');
    }

    public function test_linked_accounts_come_read_only_in_the_snapshot(): void
    {
        $savings = $this->account('Ahorro Dianita');
        $this->account('Nómina');
        Sanctum::actingAs($this->owner, ['*']);
        $path = '/api/mobile/profiles/'.$this->dianita->id.'/workspace';

        $options = collect($this->getJson($path)->assertOk()->assertJsonPath('workspace.accounts', [])->json('workspace.availableLinks'));
        $this->assertTrue($options->contains(fn (array $option) => $option['key'] === 'account:'.$savings->id && $option['label'] === 'Cuenta'));

        $this->putJson($path.'/links', ['selections' => [['type' => 'account', 'id' => $savings->id]]])->assertOk()
            ->assertJsonPath('workspace.connections.accounts', true)
            ->assertJsonPath('workspace.connections.finance', true)
            ->assertJsonCount(1, 'workspace.accounts')
            ->assertJsonPath('workspace.accounts.0.name', 'Ahorro Dianita')
            ->assertJsonPath('workspace.accounts.0.currency_code', 'DOP')
            ->assertJsonPath('workspace.accounts.0.current_balance', 0)
            ->assertJsonPath('workspace.accounts.0.closed_at', null);

        TeamMemberGrant::create(['team_id' => $this->team->id, 'user_id' => $this->guest->id, 'loger_profile_id' => $this->dianita->id, 'access' => 'write']);
        Sanctum::actingAs($this->guest, ['*']);
        $this->getJson($path)->assertOk()->assertJsonPath('workspace.accounts.0.id', $savings->id)->assertJsonPath('workspace.availableLinks', []);
        $this->putJson($path.'/links', ['selections' => []])->assertForbidden();
        $this->getJson('/api/mobile/overview')->assertForbidden();
    }
}

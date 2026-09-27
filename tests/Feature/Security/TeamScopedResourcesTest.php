<?php

namespace Tests\Feature\Security;

use App\Domains\AppCore\Models\CoreModule;
use App\Domains\AppCore\Models\Label;
use App\Domains\Housing\Models\Occurrence;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamScopedResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->teamedUser();
        $this->otherUser = $this->teamedUser();
    }

    public function test_search_input_is_bound_and_cannot_widen_the_team_scope(): void
    {
        $this->labelFor($this->user, 'Groceries');
        $this->labelFor($this->otherUser, 'Secret');

        $response = $this->actingAs($this->user)
            ->getJson('/api/labels?search='.urlencode("x' OR '1'='1' OR name LIKE '"))
            ->assertOk();

        $this->assertSame([], collect($response->json())->pluck('name')->all());

        $this->actingAs($this->user)
            ->getJson('/api/labels?search=e')
            ->assertOk()
            ->assertJsonPath('0.name', 'Groceries')
            ->assertJsonCount(1);
    }

    public function test_multi_field_search_stays_inside_the_team(): void
    {
        $theirs = $this->occurrenceFor($this->otherUser, 'Gasolina');

        $response = $this->actingAs($this->user)
            ->get("/housing/occurrence?search={$theirs->id}")
            ->assertOk();

        $ids = collect($response->viewData('page')['props']['occurrences'] ?? [])->pluck('id')->all();
        $this->assertNotContains($theirs->id, $ids);
    }

    public function test_api_resources_of_another_team_cannot_be_read_updated_or_deleted(): void
    {
        $theirs = $this->labelFor($this->otherUser, 'Secret');

        $this->actingAs($this->user)->getJson("/api/labels/{$theirs->id}")->assertNotFound();
        $this->actingAs($this->user)->putJson("/api/labels/{$theirs->id}", ['name' => 'pwned'])->assertNotFound();
        $this->actingAs($this->user)->deleteJson("/api/labels/{$theirs->id}")->assertNotFound();

        $this->assertDatabaseHas('labels', ['id' => $theirs->id, 'name' => 'Secret']);
    }

    public function test_api_update_cannot_move_a_record_to_another_team(): void
    {
        $mine = $this->labelFor($this->user, 'Groceries');

        $this->actingAs($this->user)
            ->putJson("/api/labels/{$mine->id}", ['name' => 'Food', 'team_id' => $this->otherUser->current_team_id])
            ->assertOk();

        $this->assertDatabaseHas('labels', ['id' => $mine->id, 'name' => 'Food', 'team_id' => $this->user->current_team_id]);
    }

    public function test_inertia_resources_of_another_team_cannot_be_updated_or_deleted(): void
    {
        $theirs = $this->occurrenceFor($this->otherUser, 'Gasolina');

        $this->actingAs($this->user)->put("/housing/occurrence/{$theirs->id}", ['name' => 'pwned'])->assertNotFound();
        $this->actingAs($this->user)->delete("/housing/occurrence/{$theirs->id}")->assertNotFound();

        $this->assertDatabaseHas('occurrence_checks', ['id' => $theirs->id, 'name' => 'Gasolina']);
    }

    public function test_inertia_update_keeps_the_record_in_its_team(): void
    {
        $mine = $this->occurrenceFor($this->user, 'Gasolina');

        $this->actingAs($this->user)
            ->put("/housing/occurrence/{$mine->id}", ['name' => 'Gas', 'team_id' => $this->otherUser->current_team_id])
            ->assertRedirect();

        $this->assertDatabaseHas('occurrence_checks', ['id' => $mine->id, 'name' => 'Gas', 'team_id' => $this->user->current_team_id]);
    }

    public function test_settings_section_name_is_not_interpolated_into_sql(): void
    {
        Setting::create(['team_id' => $this->user->current_team_id, 'user_id' => $this->user->id, 'name' => 'team_name', 'value' => 'Mine']);
        Setting::create(['team_id' => $this->otherUser->current_team_id, 'user_id' => $this->otherUser->id, 'name' => 'team_name', 'value' => 'Theirs']);

        $this->assertSame([], Setting::getBySection($this->user->current_team_id, "x' OR name LIKE '"));
        $this->assertSame(['team_name' => 'Mine'], Setting::getBySection($this->user->current_team_id, 'team'));
    }

    public function test_transactions_list_search_is_bound(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/finance/transactions?search='.urlencode("x'"))
            ->assertOk();
    }

    private function teamedUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();
        CoreModule::where('team_id', $user->current_team_id)->where('name', 'Housing')->update(['enabled' => true]);

        return $user;
    }

    private function labelFor(User $owner, string $name): Label
    {
        return Label::create([
            'team_id' => $owner->current_team_id,
            'user_id' => $owner->id,
            'name' => $name,
        ]);
    }

    private function occurrenceFor(User $owner, string $name): Occurrence
    {
        return Occurrence::create([
            'team_id' => $owner->current_team_id,
            'user_id' => $owner->id,
            'name' => $name,
            'last_date' => now()->subDays(3)->format('Y-m-d'),
            'is_active' => true,
        ]);
    }
}

<?php

namespace Tests\Feature\Finance;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insane\Journal\Models\Core\AccountDetailType;
use Tests\TestCase;

class AccountReorderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->user->forceFill(['current_team_id' => $this->user->ownedTeams()->first()->id])->save();
    }

    private function account(string $name, int $index, ?User $owner = null): Account
    {
        $owner ??= $this->user;

        return Account::create([
            'team_id' => $owner->ownedTeams()->first()->id,
            'user_id' => $owner->id,
            'name' => $name,
            'index' => $index,
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::BANK)->value('id'),
        ]);
    }

    public function test_saved_index_controls_the_list_and_reordering_survives_a_fresh_request(): void
    {
        $first = $this->account('First', 20);
        $second = $this->account('Second', 10);
        $archived = $this->account('Archived', 30);
        $archived->forceFill(['status' => 'disabled'])->save();

        $this->actingAs($this->user)->getJson('/api/accounts')->assertOk()
            ->assertJsonPath('0.id', $second->id)
            ->assertJsonPath('1.id', $first->id);

        $this->patchJson('/api/accounts/reorder', ['account_ids' => [$first->id, $second->id, $archived->id]])
            ->assertOk()->assertJsonPath('success', true);

        $this->getJson('/api/accounts')->assertOk()
            ->assertJsonPath('0.id', $first->id)
            ->assertJsonPath('1.id', $second->id)
            ->assertJsonPath('2.id', $archived->id);
        $this->assertSame('disabled', $archived->fresh()->status);
        $this->assertEquals([0, 1, 2], [$first->fresh()->index, $second->fresh()->index, $archived->fresh()->index]);
    }

    public function test_foreign_account_rejects_the_entire_order_without_changing_any_index(): void
    {
        $mine = $this->account('Mine', 10);
        $other = User::factory()->withPersonalTeam()->create();
        $foreign = $this->account('Foreign', 20, $other);

        $this->actingAs($this->user)->patchJson('/api/accounts/reorder', ['account_ids' => [$mine->id, $foreign->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('account_ids.1');
        $this->assertEquals(10, $mine->fresh()->index);
        $this->assertEquals(20, $foreign->fresh()->index);
    }

    public function test_duplicate_unknown_and_empty_account_lists_are_rejected(): void
    {
        $mine = $this->account('Mine', 10);
        $this->actingAs($this->user);

        foreach ([[], [$mine->id, $mine->id], [$mine->id, 99999999], ['invalid']] as $ids) {
            $this->patchJson('/api/accounts/reorder', ['account_ids' => $ids])->assertUnprocessable();
        }
        $this->assertEquals(10, $mine->fresh()->index);
    }

    public function test_reorder_cannot_mass_assign_names_balances_or_ownership(): void
    {
        $mine = $this->account('Mine', 10);
        $this->actingAs($this->user)->patchJson('/api/accounts/reorder', [
            'account_ids' => [$mine->id], 'name' => 'Changed', 'team_id' => 9999, 'opening_balance' => 10000,
        ])->assertOk();
        $this->assertSame('Mine', $mine->fresh()->name);
        $this->assertEquals($this->user->current_team_id, $mine->fresh()->team_id);
        $this->assertEquals(0, $mine->fresh()->balance);
    }

    public function test_a_nonmember_cannot_reorder_by_switching_current_team_id(): void
    {
        $mine = $this->account('Mine', 10);
        $outsider = User::factory()->withPersonalTeam()->create();
        $outsider->forceFill(['current_team_id' => $mine->team_id])->save();
        $this->actingAs($outsider)->patchJson('/api/accounts/reorder', ['account_ids' => [$mine->id]])->assertForbidden();
        $this->assertEquals(10, $mine->fresh()->index);
    }

    public function test_reordering_requires_authentication(): void
    {
        $this->patchJson('/api/accounts/reorder', ['account_ids' => [1]])->assertUnauthorized();
    }
}

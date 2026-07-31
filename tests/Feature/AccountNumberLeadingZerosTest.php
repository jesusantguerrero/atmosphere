<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insane\Journal\Models\Core\Account;
use Tests\TestCase;

/**
 * `accounts.number` holds the card's visible last four digits, which is an
 * identifier and not a quantity: `0037` and `37` are different cards. The
 * column used to be an integer, so every leading zero was silently dropped on
 * save. These pin the string behavior end to end.
 */
class AccountNumberLeadingZerosTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        return [$user, $team];
    }

    private function makeAccount(int $teamId, int $userId, ?string $number = null): Account
    {
        return Account::forceCreate([
            'team_id' => $teamId,
            'user_id' => $userId,
            'name' => 'Personal Debit Card',
            'display_id' => 'personal_debit_card',
            'description' => 'Personal Debit Card',
            'currency_code' => 'DOP',
            'number' => $number,
        ]);
    }

    public function test_it_keeps_leading_zeros_when_updating_the_account_number(): void
    {
        [$user, $team] = $this->makeUser();
        $account = $this->makeAccount($team->id, $user->id);

        $this->actingAs($user)
            ->put(route('accounts.update', $account), [
                'name' => $account->name,
                'display_id' => $account->display_id,
                'number' => '0037',
            ]);

        $this->assertSame('0037', $account->fresh()->number);
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'number' => '0037',
        ]);
    }

    public function test_it_keeps_leading_zeros_when_creating_an_account(): void
    {
        [$user, $team] = $this->makeUser();

        $account = $this->makeAccount($team->id, $user->id, '0037');

        $this->assertSame('0037', $account->fresh()->number);
    }

    public function test_it_does_not_conflate_numbers_that_differ_only_by_leading_zeros(): void
    {
        [$user, $team] = $this->makeUser();

        $padded = $this->makeAccount($team->id, $user->id, '0037');
        $bare = Account::forceCreate([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Other Card',
            'display_id' => 'other_card',
            'description' => 'Other Card',
            'currency_code' => 'DOP',
            'number' => '37',
        ]);

        $this->assertNotSame($padded->fresh()->number, $bare->fresh()->number);
        $this->assertSame(
            $padded->id,
            Account::where('team_id', $team->id)->where('number', '0037')->value('id')
        );
    }
}

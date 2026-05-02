<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateBankCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_set_bank_code_on_their_account(): void
    {
        [$user, $account] = $this->makeUserAndAccount();

        $this->actingAs($user)
            ->patch(route('finance.accounts.bank-code', ['account' => $account->id]), [
                'bank_code' => 'BHD',
            ])
            ->assertRedirect();

        $this->assertSame('BHD', $account->fresh()->bank_code);
    }

    public function test_passing_null_clears_bank_code(): void
    {
        [$user, $account] = $this->makeUserAndAccount(['bank_code' => 'BHD']);

        $this->actingAs($user)
            ->patch(route('finance.accounts.bank-code', ['account' => $account->id]), [
                'bank_code' => null,
            ])
            ->assertRedirect();

        $this->assertNull($account->fresh()->bank_code);
    }

    public function test_trims_whitespace(): void
    {
        [$user, $account] = $this->makeUserAndAccount();

        $this->actingAs($user)
            ->patch(route('finance.accounts.bank-code', ['account' => $account->id]), [
                'bank_code' => '  APAP  ',
            ])
            ->assertRedirect();

        $this->assertSame('APAP', $account->fresh()->bank_code);
    }

    /**
     * @param  array<string, mixed>  $accountAttrs
     * @return array{0: User, 1: Account}
     */
    private function makeUserAndAccount(array $accountAttrs = []): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $teamId = $user->ownedTeams()->first()->id;
        $user->forceFill(['current_team_id' => $teamId])->save();
        $user = $user->fresh();

        $account = Account::create(array_merge([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'name' => 'Saving Dollars',
            'currency_code' => 'USD',
            'display_id' => 'saving_dollars_'.uniqid(),
            'index' => 1,
        ], $accountAttrs));

        return [$user, $account];
    }
}

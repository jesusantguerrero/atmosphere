<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Add your first account" CTAs link to /finance/accounts/create, which has no
 * page: it sends the user to the accounts overview with the flag that opens the
 * add-account modal, which that page reads on desktop and mobile alike.
 */
class AccountCreateRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_opens_the_add_account_modal_on_the_accounts_overview(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();

        $this->actingAs($user)
            ->get('/finance/accounts/create?type=credit_card')
            ->assertRedirect(route('finance.accounts.index', ['newAccount' => 1, 'type' => 'credit_card']));

        $this->actingAs($user)
            ->get('/finance/accounts/create')
            ->assertRedirect(route('finance.accounts.index', ['newAccount' => 1]));
    }
}

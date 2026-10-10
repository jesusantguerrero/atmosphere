<?php

namespace Tests\Feature\Finance;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Insane\Journal\Models\Core\AccountDetailType;
use Tests\TestCase;

class AccountsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_uses_the_canonical_account_types_and_current_team(): void
    {
        (new AccountDetailTypesCreate)->create();
        $user = User::factory()->withPersonalTeam()->create(['email' => 'demo@loger.com']);
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();
        $other = User::factory()->withPersonalTeam()->create();
        $create = fn (User $owner, string $name, string $type) => Account::create([
            'team_id' => $owner->ownedTeams()->first()->id, 'user_id' => $owner->id,
            'name' => $name, 'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::firstOrCreate(['name' => $type], ['config' => []])->id,
        ]);
        $bank = $create($user, 'Bank', AccountDetailType::BANK);
        $card = $create($user, 'Banesco', AccountDetailType::CREDIT_CARD);
        $create($user, 'Payments: Banesco', 'internal_payments');
        $create($other, 'Other team bank', AccountDetailType::BANK);

        $this->actingAs($user)->get(route('finance.accounts.index'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Finance/Account')
                ->has('accounts', 2)
                ->where('accounts', fn ($accounts) => collect($accounts)->pluck('id')->sort()->values()->all() === collect([$bank->id, $card->id])->sort()->values()->all()));
    }
}

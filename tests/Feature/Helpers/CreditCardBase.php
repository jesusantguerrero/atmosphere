<?php

namespace Tests\Feature\Helpers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\AccountDetailType;
use Tests\TestCase;

abstract class CreditCardBase extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected Account $account;

    protected mixed $creditCardData;

    protected function setup(): void
    {
        parent::setup();
        $this->seed();
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->fresh()->ownedTeams()->latest('id')->first()->id;
        $user->save();
        $this->user = $user;

        $this->creditCardData = self::getData($this->user, []);
    }

    public function fundAccount(string $accountDisplayId, int $amount, $teamId)
    {
        Account::findByDisplayId($accountDisplayId, $teamId)->openBalance($amount);
    }

    public function createCreditCard(mixed $formData = [])
    {
        $this->actingAs($this->user);

        $this->post('/accounts?json=true', self::getData($this->user, [
            ...$formData,
            '',
        ]));

        return Account::latest()->first();
    }

    public static function getData(User $user, $formData = [])
    {
        return [
            ...$formData,
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'display_id' => $formData['display_id'] ?? null,
            'account_detail_type_id' => AccountDetailType::where([
                'name' => AccountDetailType::CREDIT_CARD,
            ])->value('id'),
            'name' => $formData['name'] ?? null,
            'description' => $formData['description'] ?? '',
            'currency_code' => $formData['currency_code'] ?? 'DOP',
        ];
    }
}

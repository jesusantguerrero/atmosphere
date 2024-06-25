<?php

namespace Tests\Feature\CreditCard\Helpers;

use Tests\TestCase;
use App\Models\User;
use App\Domains\CRM\Models\Client;
use App\Domains\Loans\Models\Loan;
use Insane\Journal\Models\Core\Account;
use Illuminate\Foundation\Testing\WithFaker;
use Insane\Journal\Models\Core\AccountDetailType;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class CreditCardBase extends TestCase
{
  use WithFaker;
  use RefreshDatabase;

  protected User $user;
  protected Account $account;
  protected mixed $creditCardData;

  protected function setup(): void {
    parent::setup();
    $this->seed();
    $user = User::factory()->withPersonalTeam()->create();
    $user->current_team_id = $user->fresh()->ownedTeams()->latest('id')->first()->id;
    $user->save();
    $this->user = $user;

    $this->creditCardData = self::getData($this->user, []);
  }

  public function fundAccount(string $accountDisplayId, int $amount, $teamId) {
      Account::findByDisplayId($accountDisplayId, $teamId)->openBalance($amount);
  }

  public function createCreditCard(mixed $formData = []) {
    $this->actingAs($this->user);

    $this->post('/accounts?json=true', self::getData($this->user, [
        ...$formData,
        ""
    ]));

    return Account::latest()->first();
  }

  public static function getData(User $user, $formData = []) {
        return [
            ...$formData,
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'display_id' => $formData['display_id'] ?? null,
            "account_detail_type_id" => AccountDetailType::where([
                'name' => AccountDetailType::CREDIT_CARD
            ])->select('id')->get()->id,
            'name' => $formData['name'] ?? null,
            'description' => $formData['description'] ?? "",
            'currency_code' => $formData['currency_code'] ?? "DOP",
        ];
    }
}

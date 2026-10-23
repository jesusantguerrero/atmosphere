<?php

namespace Tests\Feature\Mobile;

use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\AccountDetailType;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(AccountDetailTypesCreate::class)->create();
    }

    public function test_overview_returns_200_when_budget_target_has_deleted_category(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        Sanctum::actingAs($user->fresh());

        $type = AccountDetailType::where('name', 'bank')->first();

        Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Test Bank',
            'display_id' => 'test-bank',
            'currency_code' => 'DOP',
            'account_detail_type_id' => $type->id,
            'balance_type' => 'DEBIT',
            'type' => 1,
        ]);

        DB::table('budget_targets')->insert([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => 999999,
            'target_type' => BudgetTarget::TYPE_SPENDING,
            'amount' => 5000,
            'frequency' => 'monthly',
            'frequency_month_date' => 15,
            'notify' => true,
            'completed_at' => null,
        ]);

        $this->getJson('/api/mobile/overview')->assertOk();
    }
}

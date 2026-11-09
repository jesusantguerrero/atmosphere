<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Account;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Insane\Journal\Models\Core\Payee;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InsightsExclusionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $teamId;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-09 10:00:00');

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->teamId = $this->user->ownedTeams()->first()->id;
        $this->user->forceFill(['current_team_id' => $this->teamId])->save();
        $this->account = Account::create(['team_id' => $this->teamId, 'user_id' => $this->user->id, 'name' => 'Popular', 'currency_code' => 'DOP', 'balance_type' => 'DEBIT', 'type' => 1]);
        Sanctum::actingAs($this->user->fresh());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_insights_expose_groups_monthly_totals_payee_ids_and_saved_exclusions(): void
    {
        $house = Category::create(['name' => 'Casa', 'team_id' => $this->teamId, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);
        $food = Category::create(['name' => 'Supermercado', 'team_id' => $this->teamId, 'resource_type' => 'transactions', 'parent_id' => $house->id, 'depth' => 1, 'index' => 0]);
        $sirena = Payee::create(['team_id' => $this->teamId, 'user_id' => $this->user->id, 'name' => 'Sirena']);
        $this->expense($food, $sirena, '2026-10-12', 3000);
        $this->expense($food, $sirena, '2026-11-03', 1200);

        $this->postJson('/api/mobile/insights/exclusions', ['categoryIds' => [$food->id], 'groupIds' => []])->assertOk();

        $response = $this->getJson('/api/mobile/insights?months=3')->assertOk();

        $row = collect($response->json('expense_categories'))->firstWhere('id', $food->id);
        $this->assertSame($house->id, $row['group_id']);
        $this->assertSame('Casa', $row['group_name']);
        $this->assertEquals(['2026-09' => 0, '2026-10' => 3000, '2026-11' => 1200], $row['by_month']);
        $response->assertJsonPath('groups', [['id' => $house->id, 'name' => 'Casa']])
            ->assertJsonPath('exclusions', ['category_ids' => [$food->id], 'group_ids' => []]);
        $this->assertSame($sirena->id, collect($response->json('expense_payees'))->firstWhere('name', 'Sirena')['id']);
    }

    public function test_exclusions_validate_and_stay_in_the_household(): void
    {
        $this->postJson('/api/mobile/insights/exclusions', [])->assertUnprocessable()->assertJsonValidationErrors(['categoryIds', 'groupIds']);
        $this->postJson('/api/mobile/insights/exclusions', ['categoryIds' => [7], 'groupIds' => [3]])->assertOk();

        $this->assertSame('[7]', Setting::query()->where('team_id', $this->teamId)->where('name', 'insights_excluded_category_ids')->value('value'));
    }

    public function test_transactions_filter_by_category_and_payee(): void
    {
        $house = Category::create(['name' => 'Casa', 'team_id' => $this->teamId, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);
        $food = Category::create(['name' => 'Supermercado', 'team_id' => $this->teamId, 'resource_type' => 'transactions', 'parent_id' => $house->id, 'depth' => 1, 'index' => 0]);
        $fuel = Category::create(['name' => 'Gasolina', 'team_id' => $this->teamId, 'resource_type' => 'transactions', 'parent_id' => $house->id, 'depth' => 1, 'index' => 1]);
        $sirena = Payee::create(['team_id' => $this->teamId, 'user_id' => $this->user->id, 'name' => 'Sirena']);
        $shell = Payee::create(['team_id' => $this->teamId, 'user_id' => $this->user->id, 'name' => 'Shell']);
        $this->expense($food, $sirena, '2026-11-03', 1200);
        $this->expense($fuel, $shell, '2026-11-04', 2500);

        $byCategory = $this->getJson("/api/mobile/transactions?category_id={$food->id}")->assertOk()->json('data.data');
        $byPayee = $this->getJson("/api/mobile/transactions?payee_id={$shell->id}")->assertOk()->json('data.data');

        $this->assertSame([$food->id], array_values(array_unique(array_column($byCategory, 'category_id'))));
        $this->assertSame([$shell->id], array_values(array_unique(array_column($byPayee, 'payee_id'))));
    }

    private function expense(Category $category, Payee $payee, string $date, float $total): Transaction
    {
        return Transaction::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $category->id,
            'payee_id' => $payee->id,
            'date' => $date,
            'description' => $payee->name,
            'total' => $total,
            'direction' => Transaction::DIRECTION_CREDIT,
            'currency_code' => 'DOP',
            'status' => 'verified',
        ]);
    }
}

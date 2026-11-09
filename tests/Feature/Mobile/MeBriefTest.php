<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Account;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Insane\Journal\Models\Core\Payee;
use Tests\TestCase;

class MeBriefTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Team $team;

    private Account $account;

    private Category $group;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-09 12:00:00');

        [$this->user, $this->team, $this->account, $this->group] = $this->makeHousehold();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/me/brief')->assertUnauthorized();
    }

    public function test_period_and_month_compare_this_month_with_the_previous_and_the_baseline(): void
    {
        $food = $this->category('Supermercado');
        $this->expense($food, '2026-08-10', 9000);
        $this->expense($food, '2026-09-10', 12000);
        $this->expense($food, '2026-10-05', 3000);
        $this->expense($food, '2026-10-20', 12000);
        $this->expense($food, '2026-11-02', 4000);
        $this->expense($food, '2026-11-09', 5000);
        $this->income('2026-11-01', 80000);

        $response = $this->as($this->user)->getJson('/api/mobile/me/brief')->assertOk();

        $response->assertJsonPath('period.month', '2026-11')
            ->assertJsonPath('period.day', 9)
            ->assertJsonPath('period.days_elapsed', 9)
            ->assertJsonPath('period.days_left', 21)
            ->assertJsonPath('period.baseline_months', ['2026-08', '2026-09', '2026-10'])
            ->assertJsonPath('period.months_with_data', 3)
            ->assertJsonPath('period.currency_code', 'DOP')
            ->assertJsonPath('month.spent_to_date', 9000)
            ->assertJsonPath('month.spent_same_day_prev_month', 3000)
            ->assertJsonPath('month.prev_month_total', 15000)
            ->assertJsonPath('month.avg_3m_total', 12000)
            ->assertJsonPath('month.projected_total', 30000)
            ->assertJsonPath('month.income_to_date', 80000)
            ->assertJsonCount(30, 'month.daily_cumulative');

        $daily = collect($response->json('month.daily_cumulative'))->keyBy('day');
        $this->assertEquals(4000, $daily[2]['current']);
        $this->assertEquals(9000, $daily[9]['current']);
        $this->assertNull($daily[10]['current']);
        $this->assertEquals(3000, $daily[5]['previous']);
        $this->assertEquals(15000, $daily[30]['previous']);
    }

    public function test_categories_keep_only_big_deviations_sorted_by_size(): void
    {
        $food = $this->category('Supermercado');
        $fuel = $this->category('Gasolina');
        $phone = $this->category('Teléfono');
        $power = $this->category('Luz');
        foreach (['2026-08-10', '2026-09-10', '2026-10-10'] as $date) {
            $this->expense($food, $date, 17800);
            $this->expense($fuel, $date, 6000);
            $this->expense($phone, $date, 40000);
            $this->expense($power, $date, 5000);
        }
        $this->expense($food, '2026-11-05', 24000);
        $this->expense($fuel, '2026-11-05', 6100);
        $this->expense($phone, '2026-11-05', 42000);
        $this->expense($power, '2026-11-05', 2000);

        $categories = $this->as($this->user)->getJson('/api/mobile/me/brief')->assertOk()->json('categories');

        $this->assertSame([$food->id, $power->id], array_column($categories, 'id'));
        $this->assertEquals(6200, $categories[0]['delta_vs_avg']);
        $this->assertEquals(34.8, $categories[0]['delta_pct']);
        $this->assertSame('Test Group', $categories[0]['group_name']);
        $this->assertEquals(-3000, $categories[1]['delta_vs_avg']);

        $all = $this->as($this->user)->getJson('/api/mobile/me/brief?min_delta=0&min_pct=0')->json('categories');
        $this->assertCount(4, $all);
        $this->as($this->user)->getJson('/api/mobile/me/brief?min_delta=-1')->assertUnprocessable()->assertJsonValidationErrors('min_delta');
    }

    public function test_recurring_needs_charges_in_three_of_the_last_four_months(): void
    {
        $phone = $this->category('Teléfono');
        $claro = $this->payee('Claro');
        $shop = $this->payee('Tienda');
        foreach (['2026-08-03' => 2400, '2026-09-03' => 2450, '2026-10-03' => 2500, '2026-11-03' => 2950] as $date => $total) {
            $this->expense($phone, $date, $total, $claro);
        }
        $this->expense($phone, '2026-10-12', 900, $shop);
        $this->expense($phone, '2026-11-01', 900, $shop);

        $recurring = $this->as($this->user)->getJson('/api/mobile/me/brief')->assertOk()->json('recurring');

        $this->assertCount(1, $recurring);
        $this->assertSame($claro->id, $recurring[0]['payee_id']);
        $this->assertSame($phone->id, $recurring[0]['category_id']);
        $this->assertSame(4, $recurring[0]['months_seen']);
        $this->assertEquals(2450, $recurring[0]['median_amount']);
        $this->assertEquals(2950, $recurring[0]['this_month_amount']);
        $this->assertEquals(20.4, $recurring[0]['delta_pct']);
        $this->assertSame('2026-11-03', $recurring[0]['last_date']);
    }

    public function test_savings_rate_is_null_without_income(): void
    {
        $food = $this->category('Supermercado');
        $this->expense($food, '2026-10-10', 1000);
        $this->income('2026-09-01', 10000);
        $this->expense($food, '2026-09-10', 8000);

        $savings = collect($this->as($this->user)->getJson('/api/mobile/me/brief')->assertOk()->json('savings'))->keyBy('month');

        $this->assertEquals(20, $savings['2026-09']['savings_rate']);
        $this->assertNull($savings['2026-10']['savings_rate']);
        $this->assertArrayNotHasKey('2026-08', $savings->all());
    }

    public function test_budget_strain_lists_categories_overspent_in_two_of_three_closed_months(): void
    {
        $food = $this->category('Supermercado');
        $fuel = $this->category('Gasolina');
        foreach (['2026-08' => [-4000, 300], '2026-09' => [-6000, -100], '2026-10' => [500, 200]] as $month => [$foodAvailable, $fuelAvailable]) {
            $this->budgetMonth($food, "{$month}-01", 18000, $foodAvailable);
            $this->budgetMonth($fuel, "{$month}-01", 6000, $fuelAvailable);
            $this->expense($food, "{$month}-15", 22100);
        }
        $this->budgetMonth($food, '2026-11-01', 18000, 9000);
        $this->budgetMonth($fuel, '2026-11-01', 6000, -1500);

        $response = $this->as($this->user)->getJson('/api/mobile/me/brief')->assertOk();

        $strain = $response->json('budget_strain');
        $this->assertCount(1, $strain);
        $this->assertSame($food->id, $strain[0]['category_id']);
        $this->assertSame(2, $strain[0]['months_overspent']);
        $this->assertEquals(18000, $strain[0]['budgeted_avg_3m']);
        $this->assertEquals(22100, $strain[0]['spent_avg_3m']);
        $this->assertEquals(22500, $strain[0]['suggested_budget']);
        $response->assertJsonPath('month.budget_available_spending', 7500);
    }

    public function test_occurrences_speeding_compares_the_last_three_intervals_with_the_usual_pace(): void
    {
        $fast = $this->occurrence('Gasolina', ['2026-09-01', '2026-09-11', '2026-09-21', '2026-10-01', '2026-10-07', '2026-10-13', '2026-10-19'], 8);
        $this->occurrence('Pampers', ['2026-09-01', '2026-09-21', '2026-10-11'], 20);
        $this->occurrence('Gas', ['2026-08-01', '2026-08-21', '2026-09-10', '2026-09-30'], 20);

        $speeding = $this->as($this->user)->getJson('/api/mobile/me/brief')->assertOk()->json('occurrences_speeding');

        $this->assertCount(1, $speeding);
        $this->assertSame($fast->id, $speeding[0]['id']);
        $this->assertEquals(6, $speeding[0]['recent_avg_days']);
        $this->assertEquals(-25, $speeding[0]['change_pct']);
    }

    public function test_insights_exclusions_are_left_out_everywhere(): void
    {
        $food = $this->category('Supermercado');
        $land = $this->category('Solar');
        $business = Category::create(['name' => 'Negocio', 'team_id' => $this->team->id, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 50]);
        $hosting = $this->category('Hosting', $business);
        foreach (['2026-09-10', '2026-10-10', '2026-11-05'] as $date) {
            $this->expense($food, $date, 1000);
            $this->expense($land, $date, 500000);
            $this->expense($hosting, $date, 7000);
        }
        foreach (['2026-09-01', '2026-10-01'] as $month) {
            $this->budgetMonth($land, $month, 0, -500000);
        }
        $this->setting('insights_excluded_category_ids', json_encode([$land->id]));
        $this->setting('insights_excluded_group_ids', json_encode([$business->id]));

        $response = $this->as($this->user)->getJson('/api/mobile/me/brief?min_delta=0&min_pct=0')->assertOk();

        $response->assertJsonPath('month.spent_to_date', 1000)
            ->assertJsonPath('month.prev_month_total', 1000)
            ->assertJsonPath('budget_strain', []);
        $this->assertSame([$food->id], array_column($response->json('categories'), 'id'));
    }

    public function test_never_mixes_another_households_data(): void
    {
        [$stranger, $strangerTeam, $strangerAccount, $strangerGroup] = $this->makeHousehold();
        $strangerFood = $this->category('Supermercado', $strangerGroup, $strangerTeam);
        foreach (['2026-09-10', '2026-10-10', '2026-11-05'] as $date) {
            $this->expense($strangerFood, $date, 99000, null, $strangerTeam, $stranger, $strangerAccount);
        }
        $this->occurrence('Gasolina', ['2026-09-01', '2026-09-11', '2026-09-21', '2026-10-01', '2026-10-07', '2026-10-13', '2026-10-19'], 8, $strangerTeam, $stranger);

        $this->as($this->user)->getJson('/api/mobile/me/brief')
            ->assertOk()
            ->assertJsonPath('month.spent_to_date', 0)
            ->assertJsonPath('occurrences_speeding', [])
            ->assertJsonPath('period.months_with_data', 0);
    }

    public function test_empty_household_returns_nulls_and_empty_lists(): void
    {
        $response = $this->as($this->user)->getJson('/api/mobile/me/brief')->assertOk();

        $response->assertJsonPath('month.spent_to_date', 0)
            ->assertJsonPath('month.projected_total', 0)
            ->assertJsonPath('month.spent_same_day_prev_month', null)
            ->assertJsonPath('month.prev_month_total', null)
            ->assertJsonPath('month.avg_3m_total', null)
            ->assertJsonPath('month.daily_cumulative.0.previous', null)
            ->assertJsonPath('categories', [])
            ->assertJsonPath('recurring', [])
            ->assertJsonPath('savings', [])
            ->assertJsonPath('budget_strain', []);
    }

    public function test_uses_the_household_time_zone_for_today(): void
    {
        $this->setting('team_timezone', 'Pacific/Auckland');
        Carbon::setTestNow(Carbon::parse('2026-11-30 23:30:00', 'UTC'));

        $this->as($this->user)->getJson('/api/mobile/me/brief')
            ->assertOk()
            ->assertJsonPath('period.month', '2026-12')
            ->assertJsonPath('period.day', 1);
    }

    /**
     * @return array{User, Team, Account, Category}
     */
    private function makeHousehold(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $group = Category::create(['name' => 'Test Group', 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 99]);
        $account = Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Test Account',
            'currency_code' => 'DOP',
            'balance_type' => 'DEBIT',
            'type' => 1,
        ]);

        return [$user->fresh(), $team, $account, $group];
    }

    private function as(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('test')->plainTextToken);
    }

    private function category(string $name, ?Category $group = null, ?Team $team = null): Category
    {
        $team ??= $this->team;
        $group ??= $this->group;

        return Category::create(['name' => $name, 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => $group->id, 'depth' => 1, 'index' => 0]);
    }

    private function payee(string $name): Payee
    {
        return Payee::create(['team_id' => $this->team->id, 'user_id' => $this->user->id, 'name' => $name]);
    }

    private function expense(Category $category, string $date, float $total, ?Payee $payee = null, ?Team $team = null, ?User $user = null, ?Account $account = null): Transaction
    {
        return Transaction::create([
            'team_id' => ($team ?? $this->team)->id,
            'user_id' => ($user ?? $this->user)->id,
            'account_id' => ($account ?? $this->account)->id,
            'category_id' => $category->id,
            'payee_id' => $payee?->id,
            'date' => $date,
            'description' => $category->name,
            'total' => $total,
            'direction' => Transaction::DIRECTION_CREDIT,
            'currency_code' => 'DOP',
            'status' => 'verified',
        ]);
    }

    private function income(string $date, float $total): Transaction
    {
        return Transaction::create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'payee_id' => $this->payee('Empresa '.$date)->id,
            'date' => $date,
            'description' => 'Salario',
            'total' => $total,
            'direction' => Transaction::DIRECTION_DEBIT,
            'currency_code' => 'DOP',
            'status' => 'verified',
        ]);
    }

    private function budgetMonth(Category $category, string $month, float $budgeted, float $available): BudgetMonth
    {
        return BudgetMonth::create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'month' => $month,
            'name' => $month,
            'budgeted' => $budgeted,
            'available' => $available,
        ]);
    }

    /**
     * @param  list<string>  $log
     */
    private function occurrence(string $name, array $log, int $avgDaysPassed, ?Team $team = null, ?User $user = null): Occurrence
    {
        return Occurrence::create([
            'team_id' => ($team ?? $this->team)->id,
            'user_id' => ($user ?? $this->user)->id,
            'name' => $name,
            'last_date' => end($log),
            'avg_days_passed' => $avgDaysPassed,
            'occurrence_count' => count($log),
            'log' => $log,
            'is_active' => true,
        ]);
    }

    private function setting(string $name, string $value): void
    {
        Setting::create(['team_id' => $this->team->id, 'user_id' => $this->user->id, 'name' => $name, 'value' => $value]);
    }
}

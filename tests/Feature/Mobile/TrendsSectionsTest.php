<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\AppCore\Models\Label;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\TransactionLine;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\AccountDetailType;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TrendsSectionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $teamId;

    private Category $group;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-09 10:00:00');
        Cache::flush();
        (new AccountDetailTypesCreate)->create();

        [$this->user, $this->teamId] = $this->household();
        $this->group = $this->category('Casa', null);
        Sanctum::actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_credit_cards_split_spending_by_how_it_was_paid(): void
    {
        $card = $this->account('Visa', AccountDetailType::CREDIT_CARD, ['credit_closing_day' => 3, 'credit_limit' => 50000]);
        $bank = $this->account('Popular', AccountDetailType::BANK);
        $food = $this->category('Supermercado');
        $fuel = $this->category('Gasolina');
        $this->expense($card, $food, '2026-11-02', 3000);
        $this->expense($bank, $food, '2026-11-04', 1000);
        $this->expense($bank, $fuel, '2026-11-05', 2500);
        $this->expense($bank, $fuel, '2026-11-06', 900, status: 'draft');
        $this->expense($bank, $fuel, '2026-11-07', 4000, transfer: true);
        $this->expense($bank, $fuel, '2026-10-20', 700);

        $response = $this->getJson('/api/mobile/trends/credit-cards')->assertOk();

        $response->assertJsonPath('period', ['start' => '2026-11-01', 'end' => '2026-11-30'])
            ->assertJsonPath('has_credit_cards', true)
            ->assertJsonPath('spent_by_kind', ['cash' => 3500, 'credit_card' => 3000])
            ->assertJsonStructure(['cards', 'top_categories', 'top_payees', 'billing_cycles' => ['cards', 'total', 'subtotal', 'discount_total'], 'journey']);
        $byCategory = collect($response->json('categories_by_funding'))->keyBy('name');
        $this->assertEquals(['category_id' => $food->id, 'name' => 'Supermercado', 'total' => 4000, 'credit_card' => 3000, 'cash' => 1000], $byCategory['Supermercado']);
        $this->assertEquals(2500, $byCategory['Gasolina']['cash']);

        $this->getJson('/api/mobile/trends/credit-cards?start=2026-10-01&end=2026-10-31')
            ->assertJsonPath('spent_by_kind', ['cash' => 700, 'credit_card' => 0]);
    }

    public function test_credit_cards_validate_dates_and_never_mix_households(): void
    {
        [$stranger, $strangerTeam] = $this->household();
        $theirCard = $this->account('Ajena', AccountDetailType::CREDIT_CARD, ['credit_closing_day' => 3], $strangerTeam, $stranger);
        $this->expense($theirCard, $this->category('Ajena', $this->group, $strangerTeam), '2026-11-02', 9000, teamId: $strangerTeam, userId: $stranger->id);

        $this->getJson('/api/mobile/trends/credit-cards?start=2026-11-10&end=2026-11-01')->assertUnprocessable()->assertJsonValidationErrors('end');
        $this->getJson('/api/mobile/trends/credit-cards?start=10-11-2026')->assertUnprocessable()->assertJsonValidationErrors('start');
        $this->getJson('/api/mobile/trends/credit-cards')
            ->assertOk()
            ->assertJsonPath('has_credit_cards', false)
            ->assertJsonPath('categories_by_funding', [])
            ->assertJsonPath('spent_by_kind', ['cash' => 0, 'credit_card' => 0]);
    }

    public function test_financial_overview_returns_the_web_data_with_snake_case_keys(): void
    {
        $this->account('Popular', AccountDetailType::BANK);

        $this->getJson('/api/mobile/trends/financial-overview')
            ->assertOk()
            ->assertJsonPath('exchange_rate', 59.8)
            ->assertJsonPath('base_currency', 'USD')
            ->assertJsonPath('quote_currency', 'DOP')
            ->assertJsonStructure(['accounts', 'pinned_goals', 'available_goals', 'pinned_goal_ids', 'goal_account_links', 'goal_category_links', 'available_categories', 'bank_breakdown', 'totals']);
    }

    public function test_labels_list_insights_totals_and_transaction_filter(): void
    {
        $bank = $this->account('Popular', AccountDetailType::BANK);
        $food = $this->category('Supermercado');
        $trip = $this->label('Viaje');
        $this->label('Desayuno', 'meal');
        [, $strangerTeam] = $this->household();
        $foreign = Label::create(['team_id' => $strangerTeam, 'user_id' => $this->user->id, 'name' => 'Ajena', 'label' => 'Ajena', 'color' => '#000', 'labelable_type' => TransactionLine::class]);
        $tagged = $this->expense($bank, $food, '2026-11-03', 1800, labelId: $trip->id);
        $this->expense($bank, $food, '2026-11-04', 500);

        $this->getJson('/api/mobile/labels')->assertOk()->assertJsonPath('data', [['id' => $trip->id, 'name' => 'Viaje', 'color' => '#ff0000']]);
        $this->getJson('/api/mobile/insights?months=1')->assertOk()->assertJsonPath('expense_labels', [['id' => $trip->id, 'name' => 'Viaje', 'total' => 1800]]);

        $ids = array_column($this->getJson("/api/mobile/transactions?label_ids[]={$trip->id}")->assertOk()->json('data.transactions'), 'id');
        $this->assertSame([$tagged], $ids);
        $this->getJson("/api/mobile/transactions?label_ids[]={$foreign->id}")->assertUnprocessable()->assertJsonValidationErrors('label_ids.0');
    }

    /**
     * @return array{User, int}
     */
    private function household(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $teamId = $user->ownedTeams()->first()->id;
        $user->forceFill(['current_team_id' => $teamId])->save();

        return [$user->fresh(), $teamId];
    }

    private function category(string $name, ?Category $group = null, ?int $teamId = null): Category
    {
        $group = func_num_args() >= 2 ? $group : $this->group;

        return Category::create(['name' => $name, 'team_id' => $teamId ?? $this->teamId, 'resource_type' => 'transactions', 'parent_id' => $group?->id, 'depth' => $group ? 1 : 0, 'index' => 0]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function account(string $name, string $type, array $extra = [], ?int $teamId = null, ?User $user = null): Account
    {
        return Account::create([
            'team_id' => $teamId ?? $this->teamId,
            'user_id' => ($user ?? $this->user)->id,
            'name' => $name,
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', $type)->value('id'),
            ...$extra,
        ]);
    }

    private function label(string $name, string $kind = 'transaction'): Label
    {
        return Label::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'name' => $name,
            'label' => $name,
            'color' => $kind === 'transaction' ? '#ff0000' : '#00ff00',
            'labelable_type' => $kind === 'transaction' ? TransactionLine::class : 'meal',
        ]);
    }

    private function expense(Account $account, Category $category, string $date, float $total, string $status = 'verified', bool $transfer = false, ?int $labelId = null, ?int $teamId = null, ?int $userId = null): int
    {
        $teamId ??= $this->teamId;
        $userId ??= $this->user->id;
        $transactionId = DB::table('transactions')->insertGetId([
            'team_id' => $teamId,
            'user_id' => $userId,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'date' => $date,
            'description' => $category->name,
            'direction' => 'WITHDRAW',
            'total' => $total,
            'currency_code' => 'DOP',
            'status' => $status,
            'is_transfer' => $transfer,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lineId = DB::table('transaction_lines')->insertGetId([
            'transaction_id' => $transactionId,
            'team_id' => $teamId,
            'user_id' => $userId,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'date' => $date,
            'type' => -1,
            'amount' => $total,
            'anchor' => 1,
            'index' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($labelId) {
            DB::table('label_resource')->insert(['team_id' => $teamId, 'user_id' => $userId, 'label_id' => $labelId, 'resource_id' => $lineId, 'resource_type' => 0, 'created_at' => now(), 'updated_at' => now()]);
        }

        return $transactionId;
    }
}

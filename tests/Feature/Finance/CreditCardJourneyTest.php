<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Journal\Actions\AccountUpdate;
use App\Domains\Transaction\Services\CreditCardJourneyService;
use App\Http\Requests\CreditCardSettingsRequest;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Transaction;
use Tests\TestCase;

class CreditCardJourneyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();
        $this->user = User::factory()->withPersonalTeam()->create(['email' => 'demo@loger.com']);
        $this->user->forceFill(['current_team_id' => $this->user->ownedTeams()->first()->id])->save();
        $this->actingAs($this->user);
    }

    public function test_historical_cards_use_opening_and_closing_dates_and_team_scope(): void
    {
        $card = $this->card(['credit_opened_at' => '2025-04-04', 'closed_at' => '2026-09-15']);
        $future = $this->card(['credit_opened_at' => '2026-09-01', 'closed_at' => '2026-10-01']);
        $old = $this->card(['credit_opened_at' => '2023-01-01', 'closed_at' => '2026-02-12']);
        $unknown = $this->card();
        $report = $this->report();
        $cards = collect($report['cards'])->keyBy('id');
        $this->assertTrue($cards[$card->id]['active_in_period']);
        $this->assertFalse($cards[$old->id]['active_in_period']);
        $this->assertTrue($cards[$old->id]['closed_before_period']);
        $this->assertFalse($cards[$future->id]['closed_before_period']);
        $this->assertTrue($cards[$unknown->id]['active_in_period']);
        $this->assertSame(1, $report['missing_opening_dates']);
        $event = collect($report['events'])->firstWhere('id', $card->id.'-opened');
        $this->assertNull(collect($event['cards'])->firstWhere('id', $card->id)['balance']);
        $this->assertNotContains($unknown->id, array_column($event['cards'], 'id'));
        $this->assertSame([], app(CreditCardJourneyService::class)->report($this->user->current_team_id + 1000, '2026-08-01', '2026-08-31')['cards']);
        $this->assertCount(1, $this->report([$card->id])['cards']);
    }

    public function test_points_use_verified_purchases_and_category_rules_without_transfers(): void
    {
        $category = Category::factory()->create(['team_id' => $this->user->current_team_id, 'user_id' => $this->user->id]);
        $card = $this->card(['credit_rewards' => ['points' => 1, 'spend' => 100, 'point_value' => 0.5, 'category_rates' => [['category_id' => $category->id, 'points' => 2, 'spend' => 100]]]]);
        $this->purchase($card, $category, 12345);
        $this->purchase($card, $category, 10000, 'draft');
        $this->purchase($card, $category, 10000, 'verified', true);
        $this->purchase($card, $category, 10000, 'verified', false, '2026-07-15');
        $row = $this->report()['cards'][0];
        $this->assertSame(246, $row['points']);
        $this->assertEquals(123, $row['estimated_value']);
        $this->assertEquals(12345, $row['spent']);
    }

    public function test_comparison_reconstructs_balances_before_and_after_the_opening(): void
    {
        $category = Category::factory()->create(['team_id' => $this->user->current_team_id, 'user_id' => $this->user->id]);
        $first = $this->card(['credit_opened_at' => '2025-01-01']);
        $new = $this->card(['credit_opened_at' => '2026-08-10']);
        $this->purchase($first, $category, 500, date: '2026-08-05');
        $this->purchase($first, $category, 300, date: '2026-08-12');
        $this->purchase($new, $category, 100, date: '2026-08-10');
        $event = collect($this->report()['events'])->firstWhere('id', $new->id.'-opened');
        $this->assertCount(1, $event['before_cards']);
        $this->assertEquals(500, $event['before_cards'][0]['balance']);
        $this->assertCount(2, $event['cards']);
        $this->assertEquals(600, array_sum(array_column($event['cards'], 'balance')));
        $this->assertSame('period', collect($this->report()['events'])->last()['kind']);
    }

    public function test_unconfigured_points_are_unknown_and_multi_currency_uses_primary_currency_only(): void
    {
        $this->card();
        $multi = $this->card(['is_multi_currency' => true, 'credit_rewards' => ['points' => 1, 'spend' => 100]]);
        $category = Category::factory()->create(['team_id' => $this->user->current_team_id, 'user_id' => $this->user->id]);
        $this->purchase($multi, $category, 1000);
        $this->purchase($multi, $category, 1000, currency: 'USD');
        $rows = $this->report()['cards'];
        $this->assertNull($rows[0]['points']);
        $this->assertNull($rows[0]['estimated_value']);
        $this->assertSame(10, $rows[1]['points']);
    }

    public function test_invalid_rules_and_closing_before_opening_are_rejected(): void
    {
        $request = new CreditCardSettingsRequest;
        $this->assertTrue(Validator::make(['credit_rewards' => ['points' => 1, 'spend' => 0]], $request->rules())->fails());
        $this->assertTrue(Validator::make(['credit_opened_at' => '2025-04-04', 'closed_at' => '2025-01-01'], $request->rules())->fails());
        $this->assertFalse(Validator::make(['credit_opened_at' => null, 'closed_at' => '2025-01-01'], $request->rules())->fails());
    }

    public function test_update_persists_rules_through_vendor_account_binding(): void
    {
        $card = $this->card();
        $vendor = \Insane\Journal\Models\Core\Account::findOrFail($card->id);
        (new AccountUpdate)->update($this->user, $vendor, ['credit_opened_at' => '2025-04-04', 'credit_rewards' => ['points' => 1, 'spend' => 100]]);
        $this->assertSame('2025-04-04', $card->fresh()->credit_opened_at->toDateString());
        $this->assertEquals(100, $card->fresh()->credit_rewards['spend']);
    }

    public function test_account_edit_route_saves_real_dates_and_rejects_invalid_rewards(): void
    {
        $card = $this->card();
        $this->put(route('accounts.update', $card), [
            'credit_opened_at' => '2025-04-04',
            'closed_at' => '2026-09-15',
            'credit_rewards' => ['points' => 1, 'spend' => 100],
        ])->assertRedirect();
        $this->assertSame('2026-09-15', $card->fresh()->closed_at->toDateString());
        $this->putJson(route('accounts.update', $card), [
            'credit_rewards' => ['points' => 1, 'spend' => 0],
        ])->assertUnprocessable()->assertJsonValidationErrors('credit_rewards.spend');
    }

    public function test_rewards_cannot_reference_another_teams_category(): void
    {
        $category = Category::factory()->create(['team_id' => $this->user->current_team_id + 100, 'user_id' => $this->user->id]);
        $request = new CreditCardSettingsRequest;
        $request->setUserResolver(fn () => $this->user);
        $validator = Validator::make(['credit_rewards' => [
            'points' => 1, 'spend' => 100,
            'category_rates' => [['category_id' => $category->id, 'points' => 0, 'spend' => 100]],
        ]], $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('credit_rewards.category_rates.0.category_id'));
    }

    public function test_month_only_opening_is_saved_and_reported_with_its_precision(): void
    {
        $card = $this->card();
        $this->put(route('accounts.update', $card), [
            'credit_opened_at' => '2023-06-01', 'credit_opened_precision' => 'month',
        ])->assertRedirect();
        $this->assertSame('month', $card->fresh()->credit_opened_precision);
        $event = collect($this->report()['events'])->firstWhere('kind', 'opened');
        $this->assertSame('month', $event['date_precision']);
        $this->assertSame('2023-06-30', $event['snapshot_date']);
        $this->putJson(route('accounts.update', $card), ['credit_opened_precision' => 'year'])
            ->assertUnprocessable()->assertJsonValidationErrors('credit_opened_precision');
    }

    public function test_preparation_uses_prior_verified_records_without_counting_transfers_as_income_or_refunds_as_payments(): void
    {
        $category = Category::factory()->create(['team_id' => $this->user->current_team_id, 'user_id' => $this->user->id, 'name' => 'Food']);
        $income = Category::factory()->create(['team_id' => $this->user->current_team_id, 'user_id' => $this->user->id, 'name' => 'Ready to Assign']);
        $old = $this->card(['credit_opened_at' => '2025-01-01']);
        $new = $this->card(['credit_opened_at' => '2026-08-01', 'credit_opened_precision' => 'month']);
        $bank = $this->card(['credit_closing_day' => null, 'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::BANK)->value('id')]);
        $record = function (Account $account, Category $category, float $amount, int $type, string $date, bool $transfer = false, string $currency = 'DOP', string $description = 'Recorded movement') use ($bank): void {
            $transaction = Transaction::create(['team_id' => $this->user->current_team_id, 'user_id' => $this->user->id,
                'account_id' => $account->id, 'counter_account_id' => $transfer ? $bank->id : null, 'date' => $date, 'number' => 1,
                'description' => $description, 'total' => $amount, 'status' => 'verified', 'is_transfer' => $transfer, 'currency_code' => $currency]);
            $transaction->lines()->create(['team_id' => $this->user->current_team_id, 'user_id' => $this->user->id,
                'account_id' => $account->id, 'category_id' => $category->id, 'date' => $date, 'amount' => $amount, 'type' => $type]);
        };
        $record($bank, $income, 10000, 1, '2026-02-02');
        $record($bank, $income, 90000, 1, '2026-02-03', true);
        $record($bank, $income, 80000, 1, '2026-02-04', false, 'DOP', 'Starting Balance');
        $record($bank, $income, 80000, 1, '2026-02-04', false, 'DOP', 'Loger adjustment');
        $record($bank, $income, 100, 1, '2026-03-04', false, 'USD');
        $this->purchase($old, $category, 1000, 'verified', false, '2026-02-05');
        $record($old, $category, 200, 1, '2026-02-06');
        $record($old, $income, 700, 1, '2026-02-07', true);
        $this->purchase($old, $category, 9999, 'draft', false, '2026-04-01');
        $this->purchase($new, $category, 9999, 'verified', false, '2026-08-10');
        $record($bank, $income, 9999, 1, '2026-01-31');
        $report = $this->report([$old->id, $new->id]);
        $preparation = collect($report['events'])->firstWhere('id', $new->id.'-opened')['preparation'];
        $this->assertSame('2026-02-01', $preparation['from']);
        $this->assertSame('2026-07-31', $preparation['until']);
        $dop = collect($preparation['currencies'])->firstWhere('currency', 'DOP');
        $this->assertEquals(10000, $dop['income']);
        $this->assertEquals(800, $dop['expense']);
        $this->assertEquals(800, $dop['purchases']);
        $this->assertEquals(700, $dop['payments']);
        $this->assertSame(1, $dop['months_recorded']);
        $this->assertNull($dop['monthly'][1]['income']);
        $this->assertEquals(800, $dop['categories'][0]['amount']);
        $usd = collect($preparation['currencies'])->firstWhere('currency', 'USD');
        $this->assertEquals(100, $usd['income']);
        $this->assertNull($usd['expense']);
        $this->assertSame([], app(CreditCardJourneyService::class)->report($this->user->current_team_id + 1000, '2026-08-01', '2026-08-31')['events']);
    }

    public function test_preparation_is_empty_without_prior_movements(): void
    {
        $card = $this->card(['credit_opened_at' => '2026-08-10']);
        $this->assertSame([], collect($this->report()['events'])->firstWhere('id', $card->id.'-opened')['preparation']['currencies']);
    }

    private function report(?array $accountIds = null): array
    {
        return app(CreditCardJourneyService::class)->report($this->user->current_team_id, '2026-08-01', '2026-08-31', $accountIds);
    }

    private function card(array $attributes = []): Account
    {
        return Account::create([
            'team_id' => $this->user->current_team_id, 'user_id' => $this->user->id,
            'name' => 'Visa', 'currency_code' => 'DOP', 'credit_closing_day' => 21, 'credit_limit' => 100000,
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::CREDIT_CARD)->value('id'),
            ...$attributes,
        ]);
    }

    private function purchase(Account $card, Category $category, float $amount, string $status = 'verified', bool $transfer = false, string $date = '2026-08-15', string $currency = 'DOP'): void
    {
        $transaction = Transaction::create([
            'team_id' => $this->user->current_team_id, 'user_id' => $this->user->id,
            'account_id' => $card->id, 'date' => $date, 'description' => 'Purchase', 'number' => 1,
            'total' => $amount, 'status' => $status, 'is_transfer' => $transfer,
            'currency_code' => $currency,
        ]);
        $transaction->lines()->create([
            'team_id' => $this->user->current_team_id, 'user_id' => $this->user->id,
            'account_id' => $card->id, 'category_id' => $category->id, 'date' => $date,
            'amount' => $amount, 'type' => -1,
        ]);
    }
}

<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\AppCore\Models\Planner;
use App\Domains\Integration\Concerns\PlannedTransactionDTO;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\NextPaymentsService;
use App\Domains\Transaction\Services\PlannedTransactionService;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Models\Core\AccountDetailType;
use Tests\TestCase;

/**
 * Planned transactions that repeat every N weeks or months, like therapy paid every 28 days.
 */
class PlannedTransactionIntervalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $teamId;

    private Account $bank;

    private Category $therapy;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 10:00:00');
        app(AccountDetailTypesCreate::class)->create();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->teamId = $this->user->ownedTeams()->first()->id;
        $this->user->forceFill(['current_team_id' => $this->teamId])->save();
        $this->bank = Account::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'name' => 'Popular',
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::BANK)->value('id'),
        ]);
        $group = Category::create(['name' => 'Salud', 'team_id' => $this->teamId, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);
        $this->therapy = Category::create(['name' => 'Terapia', 'team_id' => $this->teamId, 'resource_type' => 'transactions', 'parent_id' => $group->id, 'depth' => 1, 'index' => 0]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_every_four_weeks_moves_twenty_eight_days(): void
    {
        $this->assertSame('2026-12-29', $this->service()->nextDate($this->schedule('WEEK', 4, '2026-12-01')));
        $this->assertSame('2026-12-29', $this->service()->nextDate($this->schedule('WEEKLY', 4, '2026-12-01')));
        $this->assertSame(['2026-12-01', '2026-12-29', '2027-01-26'], $this->service()->upcomingDates('2026-12-01', 'WEEK', 4, 3));
    }

    public function test_every_two_months_skips_a_month_and_keeps_month_end(): void
    {
        $this->assertSame('2027-02-01', $this->service()->nextDate($this->schedule('MONTHLY', 2, '2026-12-01')));
        $this->assertSame(['2026-08-31', '2026-10-31', '2026-12-31'], $this->service()->upcomingDates('2026-08-31', 'MONTHLY', 2, 3));
    }

    public function test_default_interval_keeps_repeating_every_unit(): void
    {
        $this->assertSame('2027-01-01', $this->service()->nextDate($this->schedule('MONTHLY', 1, '2026-12-01')));
        $this->assertSame('2026-12-08', $this->service()->nextDate($this->schedule('WEEK', 1, '2026-12-01')));
        $this->assertSame('2026-12-07', $this->service()->nextDate($this->schedule('WEEK_DAY', 3, '2026-12-04')));
        $this->assertSame('2027-01-01', $this->service()->nextDate($this->schedule('monthly', 1, '2026-12-01')));
    }

    public function test_paying_a_four_week_plan_moves_it_twenty_eight_days(): void
    {
        $plan = $this->service()->add(PlannedTransactionDTO::from([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'account_id' => $this->bank->id,
            'counter_account_id' => null,
            'payee_id' => null,
            'category_id' => $this->therapy->id,
            'date' => '2026-12-01',
            'description' => 'Terapia',
            'total' => 12000,
            'currency_code' => 'DOP',
            'direction' => Transaction::DIRECTION_CREDIT,
            'frequency' => 'WEEK',
            'frequency_interval' => 4,
        ]));
        $this->assertSame(4, $plan->schedule->frequency_interval);

        $payment = Transaction::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'account_id' => $this->bank->id,
            'category_id' => $this->therapy->id,
            'description' => 'Pago terapia',
            'date' => '2026-12-01',
            'total' => 12000,
            'direction' => Transaction::DIRECTION_CREDIT,
            'currency_code' => 'DOP',
            'status' => Transaction::STATUS_VERIFIED,
        ]);
        event(new TransactionCreated($payment, $payment->toArray()));

        $plan->refresh();
        $this->assertSame('2026-12-29', substr((string) $plan->date, 0, 10));
        $this->assertSame('2026-12-29', $plan->schedule->date->format('Y-m-d'));
        $this->assertNull($plan->schedule->completed_at);

        $listed = app(NextPaymentsService::class)->getNextPayments($this->teamId)->firstWhere('id', "planned_{$plan->id}");
        $this->assertSame('WEEK', $listed['frequency']);
        $this->assertSame(4, $listed['frequency_interval']);
    }

    public function test_the_web_form_saves_the_interval(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('transactions.store-planned'), $this->formPayload(['frequency_interval' => 4, 'frequency' => 'WEEKLY']))
            ->assertRedirect();

        $schedule = Planner::query()->where('team_id', $this->teamId)->sole();
        $this->assertSame('WEEK', $schedule->frequency);
        $this->assertSame(4, $schedule->frequency_interval);
        $this->assertSame('2026-12-01', $schedule->date->format('Y-m-d'));
        $this->assertSame(Transaction::STATUS_PLANNED, $schedule->dateable->status);
    }

    public function test_the_web_form_defaults_to_every_month_and_reads_the_end_date_picker(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('transactions.store-planned'), $this->formPayload([
                'frequency' => 'monthly',
                'end_type' => 'DATE',
                'end_date' => Carbon::parse('2027-06-30 12:00:00')->getTimestampMs(),
            ]))
            ->assertRedirect();

        $schedule = Planner::query()->where('team_id', $this->teamId)->sole();
        $this->assertSame('MONTHLY', $schedule->frequency);
        $this->assertSame(1, $schedule->frequency_interval);
        $this->assertSame('2027-06-30', $schedule->end_date->format('Y-m-d'));
    }

    public function test_the_web_form_rejects_an_interval_outside_one_to_fifty_two(): void
    {
        foreach ([0, 53, 'dos', 1.5] as $interval) {
            $this->actingAs($this->user)
                ->postJson(route('transactions.store-planned'), $this->formPayload(['frequency_interval' => $interval]))
                ->assertStatus(422)
                ->assertJsonValidationErrors('frequency_interval');
        }

        $this->assertSame(0, Planner::query()->where('team_id', $this->teamId)->count());
    }

    private function service(): PlannedTransactionService
    {
        return app(PlannedTransactionService::class);
    }

    private function schedule(string $frequency, int $interval, string $date): Planner
    {
        return new Planner(['frequency' => $frequency, 'frequency_interval' => $interval, 'date' => $date]);
    }

    /**
     * What TransactionModal posts with "Set recurrence" on.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function formPayload(array $overrides = []): array
    {
        return [
            'description' => 'Terapia',
            'date' => '2026-12-01',
            'status' => 'planned',
            'direction' => Transaction::DIRECTION_CREDIT,
            'resource_type_id' => 'MANUAL',
            'account_id' => $this->bank->id,
            'category_id' => $this->therapy->id,
            'counter_account_id' => null,
            'payee_id' => null,
            'total' => 12000,
            'currency_code' => 'DOP',
            'end_date' => null,
            'count' => null,
            'end_type' => 'NEVER',
            'interval' => 1,
            'frequency_interval' => 1,
            'frequency' => 'MONTHLY',
            'repeat_at_end_of_month' => false,
            'repeat_on_day_of_month' => null,
            'start_date' => '2026-12-01',
            'timezone_id' => 'America/Santo_Domingo',
            ...$overrides,
        ];
    }
}

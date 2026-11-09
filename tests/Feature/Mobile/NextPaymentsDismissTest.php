<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\NextPaymentDismissal;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NextPaymentsDismissTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(AccountDetailTypesCreate::class)->create();
        Carbon::setTestNow('2026-11-09 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_endpoints_require_a_token(): void
    {
        $this->postJson('/api/mobile/next-payments/dismiss', [])->assertUnauthorized();
        $this->deleteJson('/api/mobile/next-payments/dismiss', [])->assertUnauthorized();
    }

    public function test_dismiss_hides_the_payment_this_month_and_undo_brings_it_back(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        $paymentId = 'budget_'.$this->createBudgetTarget($team, $user, 'Rent', 15)->id;
        $this->createBudgetTarget($team, $user, 'Internet', 20);

        $overview = $this->as($user)->getJson('/api/mobile/overview')->assertOk()->assertJsonPath('nextPaymentsCount', 2);
        $this->assertContains($paymentId, collect($overview->json('nextPayments'))->pluck('id'));

        $this->as($user)->postJson('/api/mobile/next-payments/dismiss', ['payment_id' => $paymentId, 'period' => '2026-11'])
            ->assertOk()
            ->assertExactJson(['dismissed' => true]);
        $this->as($user)->postJson('/api/mobile/next-payments/dismiss', ['payment_id' => $paymentId, 'period' => '2026-11'])->assertOk();
        $this->assertSame(1, NextPaymentDismissal::query()->where('payment_key', $paymentId)->count());

        $overview = $this->as($user)->getJson('/api/mobile/overview')->assertJsonPath('nextPaymentsCount', 1);
        $this->assertNotContains($paymentId, collect($overview->json('nextPayments'))->pluck('id'));

        $this->as($user)->deleteJson('/api/mobile/next-payments/dismiss', ['payment_id' => $paymentId, 'period' => '2026-11'])
            ->assertOk()
            ->assertExactJson(['dismissed' => false]);

        $overview = $this->as($user)->getJson('/api/mobile/overview')->assertJsonPath('nextPaymentsCount', 2);
        $this->assertContains($paymentId, collect($overview->json('nextPayments'))->pluck('id'));
    }

    public function test_dismissing_from_another_household_never_hides_this_households_payment(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        [$stranger] = $this->makeUserAndTeam();
        $paymentId = 'budget_'.$this->createBudgetTarget($team, $user, 'Rent', 15)->id;

        $this->as($stranger)->postJson('/api/mobile/next-payments/dismiss', ['payment_id' => $paymentId, 'period' => '2026-11'])->assertOk();
        $this->as($stranger)->deleteJson('/api/mobile/next-payments/dismiss', ['payment_id' => $paymentId, 'period' => '2026-11'])->assertOk();
        $this->as($stranger)->postJson('/api/mobile/next-payments/dismiss', ['payment_id' => $paymentId, 'period' => '2026-11'])->assertOk();

        $overview = $this->as($user)->getJson('/api/mobile/overview')->assertJsonPath('nextPaymentsCount', 1);
        $this->assertContains($paymentId, collect($overview->json('nextPayments'))->pluck('id'));
    }

    public function test_dismiss_validates_payment_id_and_period(): void
    {
        [$user] = $this->makeUserAndTeam();

        $this->as($user)->postJson('/api/mobile/next-payments/dismiss', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_id', 'period']);
        $this->as($user)->deleteJson('/api/mobile/next-payments/dismiss', ['payment_id' => 'budget_1', 'period' => '2026-11-01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('period');
    }

    public function test_overview_counts_every_payment_but_lists_eight(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        foreach (range(1, 11) as $day) {
            $this->createBudgetTarget($team, $user, "Bill {$day}", $day + 10);
        }

        $this->as($user)->getJson('/api/mobile/overview')
            ->assertOk()
            ->assertJsonCount(8, 'nextPayments')
            ->assertJsonPath('nextPaymentsCount', 11);
    }

    /**
     * @return array{User, Team}
     */
    private function makeUserAndTeam(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        return [$user->fresh(), $team];
    }

    private function as(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('test')->plainTextToken);
    }

    private function createBudgetTarget(Team $team, User $user, string $name, int $dueDay): BudgetTarget
    {
        $group = Category::create(['name' => "{$name} group", 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);
        $category = Category::create(['name' => $name, 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => $group->id, 'depth' => 1, 'index' => 0]);

        return BudgetTarget::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'target_type' => BudgetTarget::TYPE_SPENDING,
            'amount' => 5000,
            'frequency' => 'monthly',
            'frequency_month_date' => $dueDay,
            'notify' => true,
        ]);
    }
}

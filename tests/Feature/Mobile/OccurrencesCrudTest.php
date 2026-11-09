<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Domains\Housing\Models\Occurrence;
use App\Models\User;
use App\Notifications\OccurrenceAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OccurrencesCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $teamId;

    private Category $fuel;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-09 10:00:00');

        [$this->user, $this->teamId] = $this->household();
        $group = Category::create(['name' => 'Auto', 'team_id' => $this->teamId, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);
        $this->fuel = Category::create(['name' => 'Gasolina', 'team_id' => $this->teamId, 'resource_type' => 'transactions', 'parent_id' => $group->id, 'depth' => 1, 'index' => 0]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_index_lists_active_rhythms_by_default_and_everything_with_all(): void
    {
        $this->occurrence('Pampers', ['log' => ['2026-10-01'], 'last_date' => '2026-10-01']);
        $this->occurrence('Pausado', ['log' => ['2026-10-01'], 'last_date' => '2026-10-01', 'is_active' => false]);
        $this->occurrence('Nuevo');

        $this->as($this->user)->getJson('/api/mobile/occurrences')
            ->assertOk()
            ->assertJsonCount(1, 'occurrences')
            ->assertJsonPath('occurrences.0.name', 'Pampers')
            ->assertJsonPath('occurrences.0.has_rule', false)
            ->assertJsonPath('occurrences.0.log', ['2026-10-01'])
            ->assertJsonStructure(['occurrences' => [['conditions', 'is_active', 'notify_on_avg', 'notify_on_last_count', 'previous_days_count']]]);

        $this->as($this->user)->getJson('/api/mobile/occurrences?all=1')->assertJsonCount(3, 'occurrences');
    }

    public function test_store_with_a_rule_fills_the_log_from_verified_transactions(): void
    {
        $this->fuelPurchase('2026-10-20');
        $this->fuelPurchase('2026-10-28');
        $this->fuelPurchase('2026-11-05', 'draft');

        $this->as($this->user)->postJson('/api/mobile/occurrences', [
            'name' => '  Gasolina  ',
            'notify_on_avg' => true,
            'conditions' => ['category_id' => [$this->fuel->id]],
        ])
            ->assertCreated()
            ->assertJsonPath('occurrence.name', 'Gasolina')
            ->assertJsonPath('occurrence.has_rule', true)
            ->assertJsonPath('occurrence.notify_on_avg', true)
            ->assertJsonPath('occurrence.log', ['2026-10-20', '2026-10-28'])
            ->assertJsonPath('occurrence.avg_days_passed', 8);
    }

    public function test_validation_rejects_missing_name_bad_rules_and_other_households_ids(): void
    {
        [$stranger, $strangerTeam] = $this->household();
        $strangerCategory = Category::create(['name' => 'Ajena', 'team_id' => $strangerTeam, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);

        $this->as($this->user)->postJson('/api/mobile/occurrences', [])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->as($this->user)->postJson('/api/mobile/occurrences', ['name' => 'X', 'conditions' => ['description' => [['operator' => 'delete', 'value' => 'a']]]])
            ->assertUnprocessable()->assertJsonValidationErrors('conditions.description.0.operator');
        $this->as($this->user)->postJson('/api/mobile/occurrences', ['name' => 'X', 'conditions' => ['groups' => array_fill(0, 6, ['category_id' => [$this->fuel->id]])]])
            ->assertUnprocessable()->assertJsonValidationErrors('conditions.groups');
        $this->as($this->user)->postJson('/api/mobile/occurrences', ['name' => 'X', 'conditions' => ['groups' => [['category_id' => [$strangerCategory->id]]]]])
            ->assertUnprocessable()->assertJsonValidationErrors('conditions.groups.0.category_id.0');
        $this->as($this->user)->postJson('/api/mobile/occurrences', ['name' => 'X', 'conditions' => ['exclude' => ['category_id' => [$strangerCategory->id]]]])
            ->assertUnprocessable()->assertJsonValidationErrors('conditions.exclude.category_id.0');

        $this->assertSame(0, Occurrence::query()->count());
    }

    public function test_update_is_partial_and_other_households_get_403(): void
    {
        $occurrence = $this->occurrence('Pampers');
        [$stranger] = $this->household();

        $this->as($this->user)->patchJson("/api/mobile/occurrences/{$occurrence->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('occurrence.name', 'Pampers')
            ->assertJsonPath('occurrence.is_active', false);

        foreach ([
            ['patchJson', '', ['name' => 'Hack']],
            ['deleteJson', '', []],
            ['postJson', '/sync', []],
            ['postJson', '/remind', []],
            ['postJson', '/instances', []],
        ] as [$method, $suffix, $body]) {
            $this->as($stranger)->{$method}("/api/mobile/occurrences/{$occurrence->id}{$suffix}", $body)->assertForbidden();
        }
        $this->assertSame('Pampers', $occurrence->fresh()->name);
    }

    public function test_a_rhythm_with_a_rule_refuses_manual_checks_in_spanish(): void
    {
        $this->user->forceFill(['language' => 'es'])->save();
        $withRule = $this->occurrence('Gasolina', ['conditions' => ['category_id' => [$this->fuel->id]]]);
        $manual = $this->occurrence('Pampers');

        $this->as($this->user)->postJson("/api/mobile/occurrences/{$withRule->id}/instances")
            ->assertUnprocessable()
            ->assertJsonPath('errors.occurrence.0', 'Este ritmo se sincroniza con sus transacciones. Usa «Sincronizar» en vez de marcarlo a mano.');

        $this->as($this->user)->postJson("/api/mobile/occurrences/{$manual->id}/instances")
            ->assertOk()
            ->assertJsonPath('last_date', '2026-11-09');
    }

    public function test_checking_the_same_day_twice_does_not_duplicate(): void
    {
        $manual = $this->occurrence('Pampers', ['log' => ['2026-10-20'], 'last_date' => '2026-10-20', 'occurrence_count' => 1]);

        $this->as($this->user)->postJson("/api/mobile/occurrences/{$manual->id}/instances")->assertJsonPath('occurrence_count', 2);
        $this->as($this->user)->postJson("/api/mobile/occurrences/{$manual->id}/instances")->assertJsonPath('occurrence_count', 2);

        $this->assertSame(1, Occurrence::query()->where('name', 'Pampers')->count());
        $this->assertSame(['2026-10-20', '2026-11-09'], $manual->fresh()->log);

        $created = app(RegisterOccurrence::class)->add($this->teamId, 'Agua', '2026-11-09');
        $this->assertSame(['2026-11-09'], $created->log);
        app(RegisterOccurrence::class)->add($this->teamId, 'Agua', '2026-11-09');
        $this->assertSame(1, Occurrence::query()->where('name', 'Agua')->count());
    }

    public function test_sync_replaces_the_log_with_transaction_dates_and_sync_all_returns_everything(): void
    {
        $occurrence = $this->occurrence('Gasolina', ['conditions' => ['category_id' => [$this->fuel->id]], 'log' => ['2026-09-01'], 'last_date' => '2026-09-01']);
        $this->fuelPurchase('2026-10-20');
        $this->fuelPurchase('2026-11-01');

        $this->as($this->user)->postJson("/api/mobile/occurrences/{$occurrence->id}/sync")
            ->assertOk()
            ->assertJsonPath('occurrence.log', ['2026-10-20', '2026-11-01'])
            ->assertJsonPath('occurrence.last_date', '2026-11-01');

        $this->occurrence('Pausado', ['is_active' => false]);
        $this->as($this->user)->postJson('/api/mobile/occurrences/sync-all')->assertOk()->assertJsonCount(2, 'occurrences');
    }

    public function test_preview_matches_without_saving_and_remind_notifies(): void
    {
        Notification::fake();
        $this->fuelPurchase('2026-10-20');
        $this->fuelPurchase('2026-10-28');

        $this->as($this->user)->postJson('/api/mobile/occurrences/preview', ['conditions' => ['category_id' => [$this->fuel->id]]])
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('dates', ['2026-10-20', '2026-10-28']);
        $this->assertSame(0, Occurrence::query()->count());

        $occurrence = $this->occurrence('Pampers', ['log' => ['2026-10-01'], 'last_date' => '2026-10-01']);
        $this->as($this->user)->postJson("/api/mobile/occurrences/{$occurrence->id}/remind")->assertOk()->assertExactJson(['reminded' => true]);
        Notification::assertSentToTimes($this->user, OccurrenceAlert::class, 2);
    }

    public function test_destroy_removes_the_rhythm(): void
    {
        $occurrence = $this->occurrence('Pampers');

        $this->as($this->user)->deleteJson("/api/mobile/occurrences/{$occurrence->id}")->assertOk()->assertExactJson(['success' => true]);

        $this->assertNull(Occurrence::query()->find($occurrence->id));
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

    private function as(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('test')->plainTextToken);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function occurrence(string $name, array $attributes = []): Occurrence
    {
        return Occurrence::withoutEvents(fn () => Occurrence::create([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'name' => $name,
            'is_active' => true,
            ...$attributes,
        ]));
    }

    private function fuelPurchase(string $date, string $status = 'verified'): void
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'category_id' => $this->fuel->id,
            'date' => $date,
            'description' => 'Shell',
            'direction' => 'WITHDRAW',
            'total' => 2500,
            'currency_code' => 'DOP',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('transaction_lines')->insert([
            'transaction_id' => $transactionId,
            'team_id' => $this->teamId,
            'user_id' => $this->user->id,
            'category_id' => $this->fuel->id,
            'date' => $date,
            'type' => -1,
            'amount' => 2500,
            'anchor' => 1,
            'index' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

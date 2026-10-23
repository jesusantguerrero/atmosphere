<?php

namespace Tests\Feature\Housing;

use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Domains\Housing\Contracts\OccurrenceNotifyTypes;
use App\Domains\Housing\Http\Controllers\OccurrenceController;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Transaction\Actions\RecategorizeTransactionLine;
use App\Domains\Transaction\Actions\SearchTransactions;
use App\Domains\Transaction\Models\TransactionLine;
use App\Domains\Transaction\Services\PlannedTransactionService;
use App\Http\Controllers\Finance\FinanceTransactionController;
use App\Jobs\RunTeamChecks;
use App\Listeners\CheckOccurrence;
use App\Models\User;
use App\Services\MultiCurrencyDisplayService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Insane\Journal\Events\TransactionUpdated;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Transaction;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class OccurrenceSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'occurrence_test', 'database.connections.occurrence_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('transactions', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('status');
            $table->timestamp('deleted_at')->nullable();
            $table->integer('team_id')->default(1);
            $table->integer('category_id')->nullable();
            $table->timestamps();
            $table->integer('account_id')->nullable();
            $table->integer('counter_account_id')->nullable();
            $table->integer('payee_id')->nullable();
        });
        Schema::create('transaction_lines', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('team_id');
            $table->integer('transaction_id');
            $table->integer('category_id');
            $table->string('concept');
            $table->integer('account_id')->nullable();
            $table->decimal('amount')->default(0);
            $table->integer('type')->default(-1);
            $table->dateTime('date');
            $table->boolean('anchor')->default(true);
            $table->boolean('is_split')->default(false);
            $table->timestamps();
        });
        foreach (['accounts', 'payees', 'categories', 'labels'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('team_id')->nullable();
            });
        }
        Schema::create('label_resource', function (Blueprint $table): void {
            $table->integer('label_id');
            $table->integer('resource_id');
            $table->string('resource_type');
        });
        Schema::create('occurrence_checks', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('team_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('notify_on_avg')->default(false);
            $table->boolean('notify_on_last_count')->default(false);
            $table->json('conditions')->nullable();
            $table->json('log')->nullable();
            $table->date('last_date')->nullable();
            $table->integer('occurrence_count')->default(0);
            $table->integer('total_days')->default(0);
            $table->integer('previous_days_count')->default(0);
            $table->float('avg_days_passed')->default(0);
            $table->timestamps();
        });
    }

    private function line(string $date, int $category = 10, int $team = 1, string $status = 'verified', string $concept = 'Groceries', ?int $account = null, ?int $payee = null): void
    {
        $transaction = DB::table('transactions')->insertGetId(['status' => $status, 'team_id' => $team, 'account_id' => $account, 'payee_id' => $payee]);
        DB::table('transaction_lines')->insert(['team_id' => $team, 'transaction_id' => $transaction, 'category_id' => $category, 'concept' => $concept, 'date' => $date]);
    }

    private function occurrence(?array $conditions = ['category_id' => [10]]): Occurrence
    {
        return Occurrence::withoutEvents(fn () => Occurrence::create(['team_id' => 1, 'name' => 'Groceries', 'conditions' => $conditions, 'last_date' => '2024-01-01', 'log' => ['2024-01-01']]));
    }

    public function test_category_only_sync_rebuilds_recent_history_once_per_day_and_is_idempotent(): void
    {
        $this->line('2026-10-20 14:00:00');
        $this->line('2026-10-01 08:00:00');
        $this->line('2026-10-20 18:00:00');
        $this->line('2026-10-22', 10, 2);
        $this->line('2026-10-23', 10, 1, 'draft');
        $this->line('2026-10-23', 20);
        $occurrence = $this->occurrence();
        $action = new RegisterOccurrence;
        $action->sync($occurrence);
        $this->assertSame(['2026-10-01', '2026-10-20'], $occurrence->fresh()->log);
        $this->assertSame('2026-10-20', $occurrence->fresh()->last_date->toDateString());
        $this->assertEquals(19, $occurrence->fresh()->avg_days_passed);
        $this->assertEquals(2, $occurrence->fresh()->occurrence_count);
        $before = $occurrence->fresh()->getAttributes();
        $action->sync($occurrence);
        $this->assertSame($before, $occurrence->fresh()->getAttributes());
    }

    public function test_search_instance_does_not_retain_conditions_between_calls(): void
    {
        $this->line('2026-10-01', 10);
        $this->line('2026-10-02', 20, concept: 'Water');
        $search = new SearchTransactions;
        $this->assertCount(1, $search->handle(['category_id' => [10]], 1));
        $this->assertCount(1, $search->handle(['description' => [['operator' => 'is', 'value' => 'Water']]], 1));
        $this->assertFalse($search->hasValidConditions(['category_id' => [], 'description' => [['operator' => 'unknown', 'value' => 'x']]]));
        $this->assertNull($search->handle(null, 1));
        $this->assertFalse($search->hasValidConditions(['description' => [['operator' => [], 'value' => 'x']]]));
    }

    public function test_empty_matches_and_manual_occurrences_preserve_existing_history(): void
    {
        $action = new RegisterOccurrence;
        $automatic = $this->occurrence();
        $action->sync($automatic);
        $this->assertSame('2024-01-01', $automatic->fresh()->last_date->toDateString());
        $this->assertSame(['2024-01-01'], $automatic->fresh()->log);
        $manual = $this->occurrence(null);
        $action->sync($manual);
        $this->assertSame(['2024-01-01'], $manual->fresh()->log);
    }

    public function test_manual_sync_returns_a_redirect_with_fresh_data_and_respects_team(): void
    {
        $this->line('2026-10-23');
        $occurrence = $this->occurrence();
        $user = new User;
        $user->forceFill(['current_team_id' => 1]);
        request()->setUserResolver(fn () => $user);
        $controller = new OccurrenceController(new Occurrence);
        $response = $controller->sync($occurrence, new RegisterOccurrence);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('2026-10-23', $occurrence->fresh()->last_date->toDateString());
        $response = $controller->syncAll();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $user->current_team_id = 2;
        $this->expectException(NotFoundHttpException::class);
        $controller->sync($occurrence, new RegisterOccurrence);
    }

    public function test_single_and_bulk_draft_approval_schedule_occurrence_checks(): void
    {
        Bus::fake();
        Gate::before(fn (?User $user) => true);
        Gate::define('update', fn (?User $user) => true);
        Gate::define('deleteBulk', fn (?User $user) => true);
        $user = new User;
        $user->forceFill(['current_team_id' => 1]);
        request()->setUserResolver(fn () => $user);
        $controller = new FinanceTransactionController(
            new \App\Domains\Transaction\Models\Transaction,
            \Mockery::mock(PlannedTransactionService::class),
            \Mockery::mock(MultiCurrencyDisplayService::class),
        );
        $this->line('2026-10-23', status: 'draft');
        $controller->approve(\App\Domains\Transaction\Models\Transaction::firstOrFail());
        Bus::assertDispatchedTimes(RunTeamChecks::class, 1);
        $this->line('2026-10-22', status: 'draft');
        $this->line('2026-10-21', status: 'draft');
        $this->line('2026-10-20', team: 2, status: 'draft');
        DB::table('transactions')->where('id', 4)->update(['team_id' => 2]);
        $controller->approveAllDrafts(request());
        Bus::assertDispatchedTimes(RunTeamChecks::class, 2);
        $this->assertSame('draft', DB::table('transactions')->where('id', 4)->value('status'));
    }

    public function test_inline_recategorization_schedules_occurrence_detection(): void
    {
        Bus::fake();
        $this->line('2026-10-23');
        DB::table('categories')->insert([['id' => 10], ['id' => 20]]);
        $budgetService = \Mockery::mock(BudgetCategoryService::class);
        $budgetService->shouldReceive('updateActivity')->twice();
        $line = TransactionLine::withoutEagerLoads()->firstOrFail();
        $category = Category::findOrFail(20);
        (new RecategorizeTransactionLine($budgetService))->handle($line, $category);
        Bus::assertDispatchedTimes(RunTeamChecks::class, 1);
        $this->assertEquals(20, $line->fresh()->category_id);
    }

    public function test_invalid_transaction_dates_do_not_abort_sync_or_invent_today(): void
    {
        $occurrence = $this->occurrence();
        $search = \Mockery::mock(SearchTransactions::class);
        $this->app->instance(SearchTransactions::class, $search);
        $lines = fn (array $dates) => new Collection(array_map(fn ($date) => new TransactionLine(['date' => $date]), $dates));
        $search->shouldReceive('handle')->once()->andReturn($lines([null, '', 'not-a-date', '2026-10-23 12:00:00']));
        (new RegisterOccurrence)->sync($occurrence);
        $this->assertSame(['2026-10-23'], $occurrence->fresh()->log);
        $search->shouldReceive('handle')->once()->andReturn($lines([null, '', 'not-a-date']));
        (new RegisterOccurrence)->sync($occurrence);
        $this->assertSame(['2026-10-23'], $occurrence->fresh()->log);
    }

    public function test_history_rendering_keeps_the_log_and_occurrence_navigation(): void
    {
        $script = <<<'JS'
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const ts = require('typescript');
const {parse, compileTemplate} = require('@vue/compiler-sfc');
const vue = require('vue');
const menuScope = {exports:{}};
vm.createContext(menuScope);
vm.runInContext(ts.transpileModule(fs.readFileSync('resources/js/domains/app/menus.ts','utf8'), {compilerOptions:{module:ts.ModuleKind.CommonJS}}).outputText, menuScope);
assert.equal(menuScope.exports.getSectionMenu(menuScope.exports.MODULES.HOUSING).find(item=>item.url==='/housing/occurrence').label, 'Occurrences');
assert.equal(JSON.parse(fs.readFileSync('resources/lang/es.json','utf8')).Occurrences, 'Ocurrencias');
const files = ['resources/js/domains/housing/components/SiteUptime.vue'];
const components = files.map(file => {
    const component = parse(fs.readFileSync(file, 'utf8')).descriptor;
    assert.deepEqual(compileTemplate({source:component.template.content, filename:file, id:file}).errors, []);
    return component;
});
const responses = Object.freeze(['2026-10-01','2026-10-15','2026-10-23']);
const historyScope = {computed:vue.computed, defineProps:()=>({responses,max:2}), withDefaults:props=>props};
vm.createContext(historyScope);
vm.runInContext(ts.transpile(components[0].scriptSetup.content.replace(/^\s*import .*;?\r?\n/gm,'')+'\n globalThis.history=lastResponses;'), historyScope);
assert.deepEqual(Array.from(historyScope.history.value), ['2026-10-15','2026-10-23']);
assert.equal(responses[0], '2026-10-01');
JS;
        $process = new Process(['node', '-e', $script], base_path());
        $process->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
    }

    private function previewUser(): User
    {
        $user = new User;
        $user->forceFill(['id' => 1, 'current_team_id' => 1]);
        $this->withoutMiddleware();
        $this->actingAs($user);

        return $user;
    }

    public function test_unsaved_rule_preview_uses_submitted_conditions_without_writing_history(): void
    {
        $this->previewUser();
        DB::table('categories')->insert([['id' => 10, 'team_id' => 1], ['id' => 20, 'team_id' => 2]]);
        $this->line('2026-10-23', 10);
        $this->line('2026-10-22', 10, team: 2);
        $occurrence = $this->occurrence();
        $before = $occurrence->fresh()->getAttributes();
        $this->postJson('/housing/occurrences/preview', ['conditions' => ['category_id' => [10]]])
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.team_id', 1);
        $this->postJson('/housing/occurrences/preview', ['conditions' => ['description' => [['operator' => 'is', 'value' => 'Water']]]])
            ->assertOk()->assertExactJson([]);
        $this->assertSame($before, $occurrence->fresh()->getAttributes());
        $this->assertSame(1, Occurrence::count());
        $this->postJson('/housing/occurrences/preview', ['conditions' => []])->assertOk()->assertExactJson([]);
    }

    public function test_preview_rejects_other_team_categories_and_unsupported_operators(): void
    {
        $this->previewUser();
        DB::table('categories')->insert(['id' => 20, 'team_id' => 2]);
        $this->postJson('/housing/occurrences/preview', ['conditions' => ['category_id' => [20]]])
            ->assertUnprocessable()->assertJsonValidationErrors('conditions.category_id.0');
        $this->postJson('/housing/occurrences/preview', ['conditions' => ['description' => [['operator' => 'delete', 'value' => 'anything']]]])
            ->assertUnprocessable()->assertJsonValidationErrors('conditions.description.0.operator');
        $this->postJson('/housing/occurrences/preview', ['conditions' => ['category_id' => ['not-an-id']]])
            ->assertUnprocessable()->assertJsonValidationErrors('conditions.category_id.0');
    }

    public function test_saving_rule_rebuilds_history_with_the_new_conditions(): void
    {
        $user = $this->previewUser();
        $this->line('2026-10-23', 20);
        $occurrence = $this->occurrence();
        $request = Request::create('/housing/occurrence/'.$occurrence->id, 'PUT', ['conditions' => ['category_id' => [20]]]);
        $request->setUserResolver(fn () => $user);
        $controller = new OccurrenceController(new Occurrence);
        $controller->update($request, $occurrence->id);
        $this->assertSame(['2026-10-23'], $occurrence->fresh()->log);
        $this->assertSame('2026-10-23', $occurrence->fresh()->last_date->toDateString());
    }

    public function test_paused_occurrences_skip_automatic_sync_and_notifications_without_losing_history(): void
    {
        $active = $this->occurrence();
        $paused = $this->occurrence();
        $paused->update(['is_active' => false]);
        $action = \Mockery::mock(RegisterOccurrence::class);
        $action->shouldReceive('sync')->once()->with(\Mockery::on(fn ($occurrence) => $occurrence->id === $active->id));
        (new RunTeamChecks('1'))->handle($action);
        $this->assertSame(['2024-01-01'], $paused->fresh()->log);
        $this->travelTo(Carbon::parse('2026-10-23'));
        foreach ([$active, $paused] as $occurrence) {
            $occurrence->update(['last_date' => '2026-10-18', 'avg_days_passed' => 8, 'previous_days_count' => 8, 'notify_on_avg' => true, 'notify_on_last_count' => true]);
        }
        foreach (OccurrenceNotifyTypes::cases() as $type) {
            $this->assertSame([$active->id], Occurrence::getForNotificationType($type)->pluck('id')->all());
        }
        $this->travelBack();
    }

    public function test_grouped_rules_keep_team_and_verified_constraints_outside_or_clauses(): void
    {
        $this->line('2026-10-01', 10, concept: 'Market', account: 100, payee: 200);
        $this->line('2026-10-02', 20, concept: 'Water', account: 101, payee: 201);
        $this->line('2026-10-03', 30, concept: 'Other', account: 101, payee: 200);
        $this->line('2026-10-04', 10, team: 2, concept: 'Market', account: 100, payee: 200);
        $this->line('2026-10-05', 10, status: 'draft', concept: 'Market', account: 100, payee: 200);
        $conditions = ['match' => 'any', 'groups' => [
            ['match' => 'all', 'category_id' => [10], 'payee_id' => [200]],
            ['match' => 'all', 'category_id' => [20], 'account_id' => [101]],
        ]];
        $search = new SearchTransactions;
        $this->assertSame([1, 2], $search->handle($conditions, 1)->pluck('id')->all());
        $conditions['exclude'] = ['description' => [['operator' => 'is', 'value' => 'Water']]];
        $this->assertSame([1], $search->handle($conditions, 1)->pluck('id')->all());
        $conditions['description'] = [['operator' => 'is', 'value' => 'Other']];
        $conditions['exclude'] = ['payee_id' => [200], 'account_id' => [101]];
        $this->assertSame([], $search->handle($conditions, 1)->pluck('id')->all());
        $this->assertNull($search->handle(['exclude' => ['category_id' => [10]]], 1));
    }

    public function test_payee_account_and_legacy_fields_have_predictable_and_or_semantics(): void
    {
        $this->line('2026-10-01', 10, concept: 'Market', account: 100, payee: 200);
        $this->line('2026-10-02', 20, concept: 'Water', account: 101, payee: 201);
        DB::table('transactions')->where('id', 1)->update(['counter_account_id' => 101]);
        $search = new SearchTransactions;
        $this->assertSame([2], $search->handle(['account_id' => [101]], 1)->pluck('id')->all());
        $this->assertSame([1], $search->handle(['payee_id' => [200]], 1)->pluck('id')->all());
        $legacy = ['category_id' => [20], 'description' => [['operator' => 'contains', 'value' => 'Market']]];
        $this->assertCount(0, $search->handle($legacy, 1));
        $this->assertSame([1, 2], $search->handle(['match' => 'any', ...$legacy], 1)->pluck('id')->all());
        $this->assertSame([1, 2], $search->handle(['category_id' => [10, 20]], 1)->pluck('id')->all());
        $group = ['groups' => [['match' => 'any', 'description' => [['operator' => 'is', 'value' => 'Market'], ['operator' => 'is', 'value' => 'Water']]]]];
        $this->assertSame([1, 2], $search->handle($group, 1)->pluck('id')->all());
    }

    public function test_advanced_preview_rules_validate_scoped_ids_and_complexity_limits(): void
    {
        $this->previewUser();
        foreach (['accounts', 'payees'] as $table) {
            DB::table($table)->insert([['id' => 100, 'team_id' => 1], ['id' => 200, 'team_id' => 2]]);
        }
        $this->line('2026-10-23', account: 100, payee: 100);
        $this->postJson('/housing/occurrences/preview', ['conditions' => ['groups' => [['match' => 'all', 'account_id' => [100], 'payee_id' => [100]]]]])
            ->assertOk()->assertJsonCount(1);
        $this->postJson('/housing/occurrences/preview', ['conditions' => ['groups' => [['payee_id' => [200]]], 'exclude' => ['account_id' => [200]]]])
            ->assertUnprocessable()->assertJsonValidationErrors(['conditions.groups.0.payee_id.0', 'conditions.exclude.account_id.0']);
        $this->postJson('/housing/occurrences/preview', ['conditions' => ['groups' => array_fill(0, 6, ['match' => 'all'])]])
            ->assertUnprocessable()->assertJsonValidationErrors('conditions.groups');
        $this->postJson('/housing/occurrences/preview', ['conditions' => ['description' => array_fill(0, 11, ['operator' => 'is', 'value' => 'x'])]])
            ->assertUnprocessable()->assertJsonValidationErrors('conditions.description');
        $this->postJson('/housing/occurrences/preview', ['conditions' => ['account_id' => array_fill(0, 101, 100)]])
            ->assertUnprocessable()->assertJsonValidationErrors('conditions.account_id');
    }

    public function test_verified_updates_schedule_automatic_detection(): void
    {
        Bus::fake();
        Event::fake();
        Event::assertListening(TransactionUpdated::class, CheckOccurrence::class);
        $listener = new CheckOccurrence;
        $transaction = new Transaction(['team_id' => 1, 'status' => 'draft']);
        $listener->handle(new TransactionUpdated($transaction));
        Bus::assertNotDispatched(RunTeamChecks::class);
        $transaction->status = 'verified';
        $listener->handle(new TransactionUpdated($transaction));
        Bus::assertDispatched(RunTeamChecks::class);
    }
}

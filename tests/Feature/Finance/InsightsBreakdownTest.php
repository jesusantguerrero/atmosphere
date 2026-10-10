<?php

namespace Tests\Feature\Finance;

use App\Domains\Transaction\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class InsightsBreakdownTest extends TestCase
{
    public function test_expense_payees_include_missing_payees_and_exclude_unrelated_transactions(): void
    {
        config(['database.default' => 'insights_test', 'database.connections.insights_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('categories', function (Blueprint $table): void {
            $table->integer('id');
            $table->integer('team_id')->nullable();
            $table->integer('parent_id')->nullable();
            $table->string('resource_type')->nullable();
            $table->string('display_id')->nullable();
            $table->string('name')->default('Category');
            $table->integer('index')->default(0);
        });
        Schema::create('payees', function (Blueprint $table): void {
            $table->integer('team_id')->default(2);
            $table->integer('id');
            $table->string('name');
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->integer('category_id');
            $table->integer('payee_id')->nullable();
            $table->string('direction');
            $table->string('status');
            $table->date('date');
            $table->decimal('total');
            $table->softDeletes();
        });
        DB::table('categories')->insert([
            ['id' => 1, 'team_id' => 2, 'parent_id' => null, 'resource_type' => 'transactions', 'display_id' => 'expenses'],
            ['id' => 2, 'team_id' => 2, 'parent_id' => 1, 'resource_type' => 'transactions', 'display_id' => null],
            ['id' => 3, 'team_id' => 3, 'parent_id' => 1, 'resource_type' => 'transactions', 'display_id' => null],
        ]);
        DB::table('payees')->insert(['id' => 1, 'name' => 'School']);
        $base = ['category_id' => 2, 'payee_id' => 1, 'direction' => 'WITHDRAW', 'status' => 'verified', 'date' => '2026-01-15', 'total' => 500];
        DB::table('transactions')->insert([
            $base,
            array_replace($base, ['payee_id' => null, 'total' => 100]),
            array_replace($base, ['payee_id' => 999, 'total' => 200]),
            array_replace($base, ['date' => '2025-12-31']),
            array_replace($base, ['date' => '2026-11-01']),
            array_replace($base, ['status' => 'draft']),
            array_replace($base, ['direction' => 'DEPOSIT']),
            array_replace($base, ['category_id' => 3]),
        ]);
        DB::table('transactions')->insert(array_replace($base, ['deleted_at' => '2026-02-01']));
        DB::connection()->getPdo()->sqliteCreateFunction('date_format', fn ($date, $format) => substr($date, 0, 7).'-01', 2);
        DB::connection()->getPdo()->sqliteCreateFunction('concat', fn (...$parts) => implode('', $parts));
        $rows = TransactionService::getExpensePayeesInPeriod(2, '2026-01-01', '2026-10-31');
        $this->assertEquals(800, $rows->sum('total'));
        $this->assertEquals($rows->sum('total'), TransactionService::getInPeriod(2, '2026-01-01', '2026-10-31')->sum('total'));
        $this->assertEquals(300, $rows->firstWhere('name', __('Without payee'))->total);
        $this->assertCount(2, $rows);
        Carbon::setTestNow('2026-12-31');
        try {
            DB::table('transactions')->insert(array_replace($base, ['date' => '2026-12-01', 'direction' => 'DEPOSIT', 'total' => 1000]));
            $incomeOnly = TransactionService::getIncomeVsExpenses(2, 0);
            $this->assertCount(0, $incomeOnly['expenses']);
            $this->assertEquals(1000, (float) (string) $incomeOnly['incomes']->first()['avg']);
        } finally {
            Carbon::setTestNow();
        }

    }

    public function test_rankings_preserve_totals_and_can_expand_all_rows(): void
    {
        $script = <<<'JS'
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const ts = require('typescript');
const { parse, compileTemplate } = require('@vue/compiler-sfc');
const filename = 'resources/js/Pages/Trends/Insights.vue';
const { descriptor } = parse(fs.readFileSync(filename, 'utf8'));
assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename, id: 'insights' }).errors, []);
assert.ok(!descriptor.template.content.includes('{ periodLabel } ·'));
assert.ok(descriptor.template.content.indexOf('v-for="tab in tabs"') > descriptor.template.content.indexOf('v-for="(st, i) in summaryStats"'));
const source = descriptor.scriptSetup.content;
const rank = source.slice(source.indexOf('const rankRows ='), source.indexOf('const payeesInRows ='));
const context = { t: key => key };
vm.createContext(context);
vm.runInContext(ts.transpile(rank + '\n globalThis.rank = rankRows;'), context);
const rows = Array.from({length: 10}, (_, i) => ({name: `Payee ${i}`, total: 100 - i}));
const top = context.rank(rows);
assert.equal(top.length, 9);
assert.equal(top[8].name, 'Others');
assert.equal(top.reduce((sum, row) => sum + row.amount, 0), rows.reduce((sum, row) => sum + row.total, 0));
assert.ok(Math.abs(top.reduce((sum, row) => sum + row.pct, 0) - 100) < 0.000001);
assert.equal(context.rank(rows, true).length, 10);
assert.equal(context.rank([]).length, 0);
const props = { data: {
 incomeExpenses: { expenses: {school: {name: 'School', total: 500}}, incomes: {employer: {name: 'Employer', total: 1000}} },
 spendingSummary: {'2026-09-01': {total:500}, '2026-10-01': {total:0}},
 monthlyFlow: [{month:'2026-09-01', income:1000}, {month:'2026-10-01',income:0}],
 netWorth: [{date_unit:'2026-10-31',assets:200,debts:-50}],
 creditCards: {hasCreditCards:true, lastCycleBalances:[{name:'Card',total:100,credit_limit:1000}], creditTotal:100,creditCapacity:1000,creditLineUsage:10},
}, metaData: {months:2, startDate:'2026-09-01',endDate:'2026-10-31',asOfDate:'2026-10-05',hasNetWorthHistory:true} };
const page = {
 defineProps: () => props, ref: value => ({value}), computed: fn => ({get value() {return fn();}}),
 useI18n: () => ({t: key => key, locale: {value:'es'}}), useTrendOptions: () => [],
 window: {logerAppSettings:{currency_code:'DOP'}}, formatMonth: value => value,
};
vm.createContext(page);
vm.runInContext(ts.transpile(source.replace(/^import .*;\r?\n/gm, '') + '\n globalThis.result = {activeTab,summaryStats,narrative,totalIn,totalOut};'), page);
for (const tab of ['patrimonio','gastos','income','cards']) {
 page.result.activeTab.value = tab;
 assert.ok(page.result.summaryStats.value.length >= 3);
 assert.ok(page.result.narrative.value.length >= 1);
}
page.result.activeTab.value = 'gastos';
assert.equal(page.result.summaryStats.value[1].value, 250);
page.result.activeTab.value = 'income';
assert.equal(page.result.summaryStats.value[1].value, 500);
assert.equal(page.result.totalOut.value, 500);
assert.equal(page.result.totalIn.value, 1000);
page.result.activeTab.value = 'cards';
props.data.creditCards.hasCreditCards = false;
assert.equal(page.result.narrative.value[0].title, 'No credit cards yet');
page.result.activeTab.value = 'patrimonio';
props.metaData.hasNetWorthHistory = false;
assert.equal(page.result.narrative.value[0].title, 'No data for this period.');
const chart = 'resources/js/Components/ChartNetworth.vue';
const chartDescriptor = parse(fs.readFileSync(chart, 'utf8')).descriptor;
assert.deepEqual(compileTemplate({source: chartDescriptor.template.content, filename: chart, id: 'networth'}).errors, []);
JS;
        $process = new Process(['node'], base_path());
        $process->setInput($script)->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
    }

    public function test_expenses_by_account_exclude_transfers_and_card_payments(): void
    {
        config(['database.default' => 'insights_test', 'database.connections.insights_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('account_detail_types', function (Blueprint $table): void {
            $table->integer('id');
            $table->string('name');
        });
        Schema::create('accounts', function (Blueprint $table): void {
            $table->integer('id');
            $table->string('name');
            $table->integer('account_detail_type_id');
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->integer('team_id')->default(2);
            $table->integer('account_id');
            $table->integer('counter_account_id')->nullable();
            $table->boolean('is_transfer')->default(false);
            $table->string('direction');
            $table->string('status')->default('verified');
            $table->date('date')->default('2026-10-10');
            $table->decimal('total');
            $table->softDeletes();
        });
        DB::table('account_detail_types')->insert([['id' => 1, 'name' => 'bank'], ['id' => 2, 'name' => 'credit_card'], ['id' => 3, 'name' => 'expense']]);
        DB::table('accounts')->insert([
            ['id' => 1, 'name' => 'Debit', 'account_detail_type_id' => 1],
            ['id' => 2, 'name' => 'Visa', 'account_detail_type_id' => 2],
            ['id' => 3, 'name' => 'Savings', 'account_detail_type_id' => 1],
            ['id' => 4, 'name' => 'Supermarket payee', 'account_detail_type_id' => 3],
        ]);
        $base = ['account_id' => 1, 'counter_account_id' => null, 'is_transfer' => false, 'direction' => 'WITHDRAW', 'total' => 100];
        DB::table('transactions')->insert([
            array_replace($base, ['counter_account_id' => 4]),
            array_replace($base, ['counter_account_id' => null, 'total' => 50]),
            array_replace($base, ['counter_account_id' => 2, 'total' => 1000]),
            array_replace($base, ['counter_account_id' => 3, 'total' => 2000]),
            array_replace($base, ['is_transfer' => true, 'total' => 3000]),
            array_replace($base, ['account_id' => 2, 'total' => 400]),
        ]);

        $rows = TransactionService::getExpensesByAccountInPeriod(2, '2026-10-01', '2026-10-31');

        $this->assertEquals(150, $rows->firstWhere('name', 'Debit')->total);
        $this->assertEquals(400, $rows->firstWhere('name', 'Visa')->total);
        $this->assertCount(2, $rows);
    }
}

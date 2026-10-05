<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ReconciliationHubPresentationTest extends TestCase
{
    public function test_groups_prioritize_pending_and_do_not_mark_new_movements_up_to_date(): void
    {
        $script = <<<'JS'
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const ts = require('typescript');
const { parse, compileTemplate } = require('@vue/compiler-sfc');
const source = fs.readFileSync('resources/js/Pages/Finance/Reconciliation/Hub.vue', 'utf8');
const { descriptor } = parse(source);
assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename: 'Hub.vue', id: 'hub' }).errors, []);
for (const filename of ['resources/js/Pages/Finance/AccountReconciliationForm.vue', 'resources/js/Pages/Finance/Reconciliation/Show.vue']) {
 const component = parse(fs.readFileSync(filename, 'utf8')).descriptor;
 assert.deepEqual(compileTemplate({ source: component.template.content, filename, id: filename }).errors, []);
}
const script = descriptor.scriptSetup.content.replace(/^import .*;\r?\n/gm, '');
const accounts = [
 { id: 1, last_date: '2026-10-01', days_since: 3, last_status: 'completed', unreconciled_count: 2 },
 { id: 2, last_date: '2026-10-01', days_since: 3, last_status: 'completed', unreconciled_count: 0 },
 { id: 3, last_date: null, unreconciled_count: 0 },
 { id: 4, last_id: 40, last_date: '2026-01-01', days_since: 90, last_status: 'pending', unreconciled_count: 2 },
 { id: 5, last_date: '2026-01-01', days_since: 90, last_status: 'completed', unreconciled_count: 0 },
];
const visits = [];
const context = { ref: value => ({ value }), computed: fn => ({ get value() { return fn(); } }), defineProps: () => ({ accounts }), withDefaults: p => p, useI18n: () => ({ t: k => k }), router: { visit: url => visits.push(url) } };
vm.createContext(context);
vm.runInContext(ts.transpile(script + '\n globalThis.results = { accountGroups, statusOf, attentionCount, goReconcile, accountToReconcile };'), context);
const result = context.results;
assert.equal(result.statusOf(accounts[0]).key, 'review');
assert.equal(result.statusOf(accounts[1]).key, 'ok');
assert.equal(result.statusOf(accounts[2]).key, 'never');
assert.equal(result.statusOf(accounts[4]).key, 'overdue');
assert.equal(result.attentionCount.value, 4);
assert.deepEqual(JSON.parse(JSON.stringify(result.accountGroups.value.map(g => [g.key, g.accounts.map(a => a.id)]))), [['pending', [4]], ['review', [1, 3, 5]], ['ok', [2]]]);
result.goReconcile(accounts[3]);
result.goReconcile(accounts[0]);
assert.deepEqual(visits, ['/finance/reconciliation/40']);
assert.equal(result.accountToReconcile.value.id, 1);
accounts.splice(0);
assert.equal(result.accountGroups.value.length, 0);
assert.equal(result.attentionCount.value, 0);
JS;

        $process = new Process(['node', '-e', $script], dirname(__DIR__, 2));
        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
    }
}

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
const quickPosts = [];
context.useForm = initial => ({ ...initial, processing: false, clearErrors() {}, setError() {}, post(url, options) { quickPosts.push({ url, balance: this.balance, date: this.date }); options.onFinish(); } });
context.format = () => '2026-10-04';
context.axios = { get: async () => ({ data: { balance: 125 } }) };
vm.createContext(context);
vm.runInContext(ts.transpile(script + '\n globalThis.results = { accountGroups, statusOf, attentionCount, goReconcile, accountToReconcile, quickReconcile, quickConfirmation, cancelQuickReconciliation, confirmQuickReconciliation };'), context);
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
result.accountToReconcile.value = null;
result.quickReconcile({ id: 10 }).then(async () => {
 assert.equal(result.accountToReconcile.value, null);
 assert.equal(quickPosts.length, 0);
 assert.equal(result.quickConfirmation.value.id, 10);
 result.cancelQuickReconciliation();
 result.confirmQuickReconciliation();
 assert.equal(quickPosts.length, 0);
 await result.quickReconcile({ id: 10 });
 result.confirmQuickReconciliation();
 assert.deepEqual(quickPosts, [{ url: '/finance/reconciliation/accounts/10', balance: 125, date: '2026-10-04' }]);
}).catch(error => { console.error(error); process.exitCode = 1; });
accounts.splice(0);
assert.equal(result.accountGroups.value.length, 0);
assert.equal(result.attentionCount.value, 0);
const formSource = parse(fs.readFileSync('resources/js/Pages/Finance/AccountReconciliationForm.vue', 'utf8')).descriptor.scriptSetup.content.replace(/^import .*;\r?\n/gm, '');
const submissions = [];
let form;
const formContext = {
 ref: value => ({ value }), computed: fn => ({ get value() { return fn(); } }), watch: () => {},
 defineEmits: () => () => {}, defineProps: () => ({ account: { id: 10, balance: 999 }, startDetailed: true }), withDefaults: p => p,
 useForm: initial => (form = { ...initial, processing: false, transform(fn) { this.transformer = fn; return this; }, post(url) { submissions.push({ url, data: this.transformer(this) }); } }),
 format: () => '2026-10-04',
};
vm.createContext(formContext);
vm.runInContext(ts.transpile(formSource + '\n globalThis.result = { ledgerBalanceAt, loadingBalance, reconcileMatchingBalance, previewDifference };'), formContext);
const shortcut = formContext.result;
shortcut.reconcileMatchingBalance();
assert.equal(submissions.length, 0);
shortcut.ledgerBalanceAt.value = 0;
shortcut.reconcileMatchingBalance();
assert.equal(submissions[0].data.balance, 0);
assert.equal(shortcut.previewDifference.value, 0);
shortcut.ledgerBalanceAt.value = -75;
shortcut.reconcileMatchingBalance();
assert.equal(submissions[1].data.balance, -75);
assert.equal(submissions[1].url, '/finance/reconciliation/accounts/10');
shortcut.loadingBalance.value = true;
shortcut.reconcileMatchingBalance();
assert.equal(submissions.length, 2);
shortcut.loadingBalance.value = false;
form.processing = true;
shortcut.reconcileMatchingBalance();
assert.equal(submissions.length, 2);
JS;

        $process = new Process(['node', '-e', $script], dirname(__DIR__, 2));
        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
    }
}

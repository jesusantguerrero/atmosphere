<?php

namespace Tests\Feature\Finance;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class DashboardPresentationTest extends TestCase
{
    public function test_dashboard_balances_and_mobile_templates(): void
    {
        $script = <<<'JS'
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const ts = require('typescript');
const {parse, compileTemplate} = require('@vue/compiler-sfc');
const filename = 'resources/js/Pages/Dashboard/Partials/DashboardSummary.vue';
const descriptor = parse(fs.readFileSync(filename,'utf8')).descriptor;
assert.deepEqual(compileTemplate({source:descriptor.template.content,filename,id:'dashboard'}).errors, []);
const source = descriptor.scriptSetup.content;
const start = source.indexOf('const numericBalance =');
const end = source.indexOf('const movementIsPositive');
const scrollContext = { document: {body:{style:{overflow:'auto'}},documentElement:{style:{overflow:''}}}, exports:{} };
vm.createContext(scrollContext);
vm.runInContext(ts.transpile(fs.readFileSync('resources/js/Components/atoms/modalScrollLock.ts','utf8')),scrollContext);
const first = Symbol(), second = Symbol();
scrollContext.exports.lockModalScroll(first);
scrollContext.exports.unlockModalScroll(Symbol());
assert.equal(scrollContext.document.body.style.overflow,'hidden');
scrollContext.exports.lockModalScroll(second);
scrollContext.exports.unlockModalScroll(first);
assert.equal(scrollContext.document.documentElement.style.overflow,'hidden');
scrollContext.exports.unlockModalScroll(second);
assert.equal(scrollContext.document.body.style.overflow,'auto');
assert.equal(scrollContext.document.documentElement.style.overflow,'');
const props = {accounts:[
 {balance:'-200',detail_type:{name:'credit_card'}},
 {balance:'50',detail_type:{name:'credit_card'}},
 {balance:'1000',credit_limit:5000,detail_type:{name:'bank'}},
 {balance:'invalid',detail_type:{name:'credit_card'}},
]};
const context = {props,computed:fn=>({get value(){return fn();}})};
vm.createContext(context);
vm.runInContext(ts.transpile(source.slice(start,end)+'\n globalThis.result={creditCardDebt,totalBalance};'),context);
assert.equal(context.result.creditCardDebt.value,150);
assert.equal(context.result.totalBalance.value,850);
props.accounts = [{balance:50,detail_type:{name:'credit_card'}}];
assert.equal(context.result.creditCardDebt.value,0);
props.accounts = [];
assert.equal(context.result.creditCardDebt.value,0);
assert.ok(descriptor.template.content.includes('col-span-2 md:col-span-1'));
assert.ok(!descriptor.template.content.includes('mt-1 truncate'));
assert.ok(descriptor.template.content.includes('visibleOverduePayments'));
assert.ok(descriptor.template.content.includes('visiblePendingPayments'));
for (const path of ['resources/js/Components/atoms/Modal.vue','resources/js/domains/transactions/components/TransactionModal.vue']) {
 const d = parse(fs.readFileSync(path,'utf8')).descriptor;
 assert.deepEqual(compileTemplate({source:d.template.content,filename:path,id:'modal'}).errors,[]);
 if (path.endsWith('/Modal.vue')) {
  assert.ok(d.template.content.includes("fullHeight ? 'overflow-hidden' : 'overflow-y-auto'"));
  assert.ok(d.template.content.includes('h-[100dvh]'));
 }
}
JS;
        $process = new Process(['node'], base_path());
        $process->setInput($script)->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
    }
}

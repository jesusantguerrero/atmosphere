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
assert.ok(descriptor.template.content.includes('col-span-2 md:col-span-1'));
assert.ok(descriptor.template.content.includes('v-for="payment in priorityPayments"'));
assert.ok(descriptor.template.content.includes('@click="handlePay(payment)"'));
assert.ok(descriptor.template.content.includes(':compact="true"'));
for (const removed of ['AccountBalancesWidget','WatchlistDashboardWidget','OccurrenceWidget','RoutineNowNextWidget','Net credit card debt']) assert.ok(!descriptor.template.content.includes(removed));
const context = {props:{nextPayments:[]}, overduePayments:{value:[{id:1},{id:2}]}, dueSoonPayments:{value:[{id:3},{id:4}]},computed:fn=>({get value(){return fn();}})};
vm.createContext(context);
const priority = source.slice(source.indexOf('const priorityPayments ='),source.indexOf('// ---------------------------------------------------------------------------\n// Issue 1'));
vm.runInContext(ts.transpile(priority+'\n globalThis.result=priorityPayments;'),context);
assert.deepEqual(Array.from(context.result.value,x=>x.id),[1,2,3]);
context.overduePayments.value=[];context.dueSoonPayments.value=[];
assert.equal(context.result.value.length,0);
const agenda = parse(fs.readFileSync('resources/js/Pages/Dashboard/Partials/TodayAgendaWidget.vue','utf8')).descriptor.scriptSetup.content;
const agendaContext = {props:{date:'2026-10-05',events:[{id:'past',start:'2026-10-05',time:'08:00'},{id:'next',start:'2026-10-05',time:'14:00'},{id:'other',start:'2026-10-06',time:'09:00'}]},googleEvents:{value:[{id:'all',start:'2026-10-05'},{id:'later',start:'2026-10-05',time:'16:00'}]},clock:{value:new Date('2026-10-05T12:00:00')},computed:fn=>({get value(){return fn();}})};
vm.createContext(agendaContext);
vm.runInContext(ts.transpile(agenda.slice(agenda.indexOf('const isPast ='),agenda.indexOf('onMounted('))+'\n globalThis.result=visibleEvents;'),agendaContext);
assert.deepEqual(Array.from(agendaContext.result.value,x=>x.id),['all','next','later']);
agendaContext.props.events=[];agendaContext.googleEvents.value=[];
assert.equal(agendaContext.result.value.length,0);
for (const path of ['resources/js/Components/atoms/Modal.vue','resources/js/domains/transactions/components/TransactionModal.vue','resources/js/domains/meal/components/MealWidget.vue','resources/js/Pages/Dashboard/Index.vue','resources/js/Pages/Dashboard/Partials/TodayAgendaWidget.vue']) {
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

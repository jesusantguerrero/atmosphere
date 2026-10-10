<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class CardHistoryPresentationTest extends TestCase
{
    public function test_averages_and_insights_link_keep_the_historical_window_and_missing_values(): void
    {
        $script = <<<'JS'
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const ts = require('typescript');
const { parse, compileTemplate } = require('@vue/compiler-sfc');
const vue = require('vue');
const { renderToString } = require('@vue/server-renderer');
const journey = parse(fs.readFileSync('resources/js/Pages/Trends/Partials/CreditCardJourney.vue', 'utf8')).descriptor;
assert.deepEqual(compileTemplate({ source: journey.template.content, filename: 'Journey.vue', id: 'journey' }).errors, []);
const helpers = journey.scriptSetup.content.split('const recordedMoney')[1].split('const preparationInsights')[0];
const context = { money: (value, currency) => `${currency} ${value.toFixed(2)}`, route: (name, params) => ({ name, ...params }) };
vm.createContext(context);
vm.runInContext(ts.transpile('const recordedMoney' + helpers + '\n globalThis.result = { monthlyAverage, preparationHref };'), context);
assert.equal(context.result.monthlyAverage(6000, 'DOP'), 'DOP 1000.00');
assert.equal(context.result.monthlyAverage(0, 'DOP'), 'DOP 0.00');
assert.equal(context.result.monthlyAverage(null, 'DOP'), 'Sin registros');
assert.equal(context.result.monthlyAverage(60, 'USD'), 'USD 10.00');
assert.deepEqual(JSON.parse(JSON.stringify(context.result.preparationHref({from:'2023-08-01', until:'2024-01-31'}))), { name:'finance.trends', months:6, range:'Custom', end:'2024-01' });
const formatting = journey.scriptSetup.content.split('const money =')[1].split('const eventLabel')[0];
const formattingContext = {};
vm.createContext(formattingContext);
vm.runInContext(ts.transpile('const money =' + formatting + '\n globalThis.formatMoney = money;'), formattingContext);
assert.ok(formattingContext.formatMoney(100, 'RD').startsWith('RD '));
assert.ok(formattingContext.formatMoney(100, 'DOP').includes('DOP'));
const account = parse(fs.readFileSync('resources/js/Pages/Finance/Account.vue', 'utf8')).descriptor;
const editButton = account.template.content.match(/<button[^>]*@click="isAccountModalOpen = true"[^>]*>[\s\S]*?<\/button>/)[0];
const editModal = account.template.content.match(/<AccountModal v-if="isAccountModalOpen"[^>]*\/>/)[0];
const editCompiled = compileTemplate({ source:editButton + editModal, filename:'EditAccount.vue', id:'edit-account' });
const editScope = { exports:{}, require };
vm.createContext(editScope);
vm.runInContext(ts.transpile(editCompiled.code, {module:ts.ModuleKind.CommonJS}), editScope);
const editContext = { isAccountModalOpen:false, selectedAccount:{id:1667,name:'APAP Sirena'}, context:{isMobile:true} };
let editTree = editScope.exports.render(editContext, []);
editTree.children[0].props.onClick();
assert.equal(editContext.isAccountModalOpen,true);
editTree = editScope.exports.render(editContext, []);
assert.equal(editTree.children[1].props['form-data'].id,1667);
assert.equal(editTree.children[1].props['max-width'],'mobile');
editTree.children[1].props.onClose();
assert.equal(editContext.isAccountModalOpen,false);
const compiled = compileTemplate({ source: account.template.content, filename:'Account.vue', id:'account' });
assert.deepEqual(compiled.errors, []);
const moduleScope = { exports: {}, require };
vm.createContext(moduleScope);
vm.runInContext(ts.transpile(compiled.code, {module:ts.ModuleKind.CommonJS}), moduleScope);
const wrapper = { render() { return vue.h('section', [this.$slots.title?.(), this.$slots.header?.(), this.$slots.default?.()]); } };
const app = vue.createSSRApp({ render:moduleScope.exports.render, data:() => ({ selectedAccount:undefined, accounts:[], router:{visit(){}}, saveAccountsReorder(){} }) });
app.config.globalProperties.$t = value => value;
app.component('AppLayout',wrapper);
app.component('FinanceTemplate',wrapper);
app.component('FinanceSectionNav',{render:() => vue.h('nav')});
app.component('AccountsOverview',{render:() => vue.h('div','Accounts overview')});
renderToString(app).then(html => { assert.ok(html.includes('Accounts overview')); assert.ok(html.includes('Accounts')); }).catch(error => { console.error(error); process.exitCode = 1; });
const overview = parse(fs.readFileSync('resources/js/Pages/Finance/Partials/AccountsOverview.vue','utf8')).descriptor;
assert.deepEqual(compileTemplate({source:overview.template.content,filename:'Overview.vue',id:'overview'}).errors,[]);
const accounts = [
 {id:1,name:'Bank DOP',currency_code:'DOP',balance:1000,archived:'0'},
 {id:2,name:'Banesco',bank_code:'Banesco',currency_code:'DOP',balance:-200,credit_limit:1000,credit_closing_day:15},
 {id:3,name:'Closed',currency_code:'DOP',balance:-9999,credit_limit:20000,credit_closing_day:20,closed_at:'2026-01-01'},
 {id:4,name:'USD bank',currency_code:'USD',balance:50},
];
const overviewContext = {computed:vue.computed,ref:vue.ref,onMounted:()=>{},defineProps:()=>({accounts}),useAppContextStore:()=>({isMobile:false}),useAccountsStore:()=>({accounts:[]})};
vm.createContext(overviewContext);
vm.runInContext(ts.transpile(overview.scriptSetup.content.replace(/^import .*;\r?\n/gm,'') + '\n globalThis.result = { filtered, filter, search, summaries, reconciliation };'),overviewContext);
const o = overviewContext.result;
assert.equal(o.filtered.value.length,4);
o.filter.value='active';
assert.equal(o.filtered.value.length,3);
assert.deepEqual(JSON.parse(JSON.stringify(o.summaries.value)),[{currency:'DOP',cash:1000,debt:200,available:800},{currency:'USD',cash:50,debt:0,available:0}]);
o.filter.value='cards'; assert.equal(o.filtered.value[0].id,2);
o.filter.value='closed'; assert.equal(o.filtered.value[0].id,3);
o.filter.value='active'; o.search.value='banesco'; assert.equal(o.filtered.value[0].id,2);
assert.equal(o.reconciliation({balance:100,reconciliation_last:{status:'completed',amount:'100.00'}}),'Conciliada');
assert.equal(o.reconciliation({balance:110,reconciliation_last:{status:'completed',amount:'100.00'}}),'Por revisar');
assert.equal(o.reconciliation({balance:100,reconciliation_last:{status:'pending',amount:100}}),'Conciliación pendiente');
JS;
        $process = new Process(['node', '-e', $script], dirname(__DIR__, 2));
        $process->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
    }
}

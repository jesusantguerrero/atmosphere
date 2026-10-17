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
for(const file of ['InlineTransactionCategory.vue','TransactionSearch.vue','TransactionsList.vue']) {
 const component=parse(fs.readFileSync('resources/js/domains/transactions/components/'+file,'utf8')).descriptor;
 assert.deepEqual(compileTemplate({source:component.template.content,filename:file,id:file}).errors,[]);
}
const inlineCategory=parse(fs.readFileSync('resources/js/domains/transactions/components/InlineTransactionCategory.vue','utf8')).descriptor;
const requests=[],saved=[];
const inlineScope={ref:vue.ref,inject:()=>[],defineProps:()=>({transaction:{category_line_id:123,category_id:1}}),defineEmits:()=>event=>saved.push(event),axios:{patch:async(url,data)=>{requests.push({url,data});}}};
vm.createContext(inlineScope);
vm.runInContext(ts.transpile(inlineCategory.scriptSetup.content.replace(/^import .*;\r?\n/gm,'')+'\n globalThis.result={save,selected,saving,error};'),inlineScope);
(async()=>{
 await inlineScope.result.save(2);
 assert.equal(requests[0].url,'/finance/transaction-lines/123/category');
 assert.equal(requests[0].data.category_id,2);
 assert.equal(inlineScope.result.selected.value,2);
 assert.equal(saved[0],'saved');
 inlineScope.axios.patch=async()=>{throw new Error('Failed');};
 await inlineScope.result.save(3);
 assert.equal(inlineScope.result.selected.value,2);
 assert.equal(inlineScope.result.error.value,true);
 assert.equal(inlineScope.result.saving.value,false);
})().catch(error=>{console.error(error);process.exitCode=1;});
const moneyScope={window:{logerAppSettings:{currency_code:'DOP'}},exports:{}};
vm.createContext(moneyScope);
vm.runInContext(ts.transpile(fs.readFileSync('resources/js/utils/formatMoney.ts','utf8'),{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2020}),moneyScope);
for(const amount of [-0,-1e-12,-0.004,0,0.004]) {
 assert.equal(moneyScope.exports.formatMoney(amount),moneyScope.exports.formatMoney(0));
}
assert.ok(moneyScope.exports.formatMoney(-0.01).includes('-'));
assert.equal(moneyScope.exports.formatMoney(-0.4,'JPY'),moneyScope.exports.formatMoney(0,'JPY'));
const budgetPage=parse(fs.readFileSync('resources/js/Pages/Finance/Budget.vue','utf8')).descriptor;
const balanceProps=vue.ref({balance:-1e-12});
const balanceScope={computed:vue.computed,readyToAssign:balanceProps};
vm.createContext(balanceScope);
vm.runInContext(ts.transpile('const readyToAssignBalance ='+budgetPage.scriptSetup.content.split('const readyToAssignBalance =')[1].split('const readyToAssignLeft')[0]+'\n globalThis.balance=readyToAssignBalance;'),balanceScope);
assert.equal(balanceScope.balance.value,0);
balanceProps.value.balance=-0.01;
assert.equal(balanceScope.balance.value,-0.01);
const spanish=JSON.parse(fs.readFileSync('resources/lang/es.json','utf8'));
for (const [filename,keys] of [['BudgetTargetForm.vue',['Set target','Cancel','Save']],['BudgetMatchForm.vue',['Set Account Match','Cancel','Save']]]) {
 const component=parse(fs.readFileSync('resources/js/domains/budget/components/'+filename,'utf8')).descriptor;
 assert.deepEqual(compileTemplate({source:component.template.content,filename,id:filename}).errors,[]);
 for(const key of keys) {
  assert.ok(component.template.content.includes("$t('"+key+"')"));
  assert.ok(spanish[key] && spanish[key]!==key);
 }
}
assert.equal(spanish['Add a note...'],'Añade una nota…');
for (const file of ['resources/js/Pages/Finance/Partials/FinanceTemplate.vue','resources/js/domains/budget/components/BudgetOnboarding.vue','resources/js/Pages/Finance/Budget.vue','resources/js/Pages/Finance/Partials/BudgetCategories.vue','resources/js/domains/budget/components/BudgetGroupItem.vue','resources/js/domains/budget/components/BudgetItem.vue','resources/js/domains/budget/components/BudgetReconcileBanner.vue']) {
 const component=parse(fs.readFileSync(file,'utf8')).descriptor;
 assert.deepEqual(compileTemplate({source:component.template.content,filename:file,id:file}).errors,[]);
}
const banner=parse(fs.readFileSync('resources/js/domains/budget/components/BudgetReconcileBanner.vue','utf8')).descriptor;
const bannerProps={accounts:[{id:1,currency_code:'DOP',balance:1000},{id:2,currency_code:'DOP',balance:-200,credit_closing_day:15},{id:3,currency_code:'USD',balance:50}],budgets:{data:[{subCategories:[{available:700},{account_id:2,available:200},{account_id:3,available:50}]}]},readyToAssign:{balance:100},startDate:'2026-10-01',endDate:'2026-10-31'};
const bannerScope={computed:vue.computed,window:{logerAppSettings:{currency_code:'DOP'}},defineProps:()=>bannerProps,format:date=>date.toISOString().slice(0,10)};
vm.createContext(bannerScope);
vm.runInContext(ts.transpile(banner.scriptSetup.content.replace(/^import .*;\r?\n/gm,'')+'\n globalThis.result={rows,hasGap,uncategorizedHref};',{target:ts.ScriptTarget.ES2020}),bannerScope);
assert.equal(bannerScope.result.hasGap.value,false);
assert.deepEqual(JSON.parse(JSON.stringify(bannerScope.result.rows.value)),[{cur:'DOP',cash:1000,assigned:1000,uncategorized:0},{cur:'USD',cash:50,assigned:50,uncategorized:0}]);
assert.equal(bannerScope.result.uncategorizedHref('DOP'),'/finance/transactions?filter[uncategorized]=1&filter[currency_code]=DOP');
assert.ok(!bannerScope.result.uncategorizedHref('DOP').includes('filter[date]'));
assert.ok(banner.template.content.includes("$t('Difference to review')"));
const cardItem=parse(fs.readFileSync('resources/js/domains/budget/components/BudgetItem.vue','utf8')).descriptor;
assert.ok(cardItem.template.content.includes(':href="brechaHref"'));
assert.ok(cardItem.template.content.includes("$t('you owe')"));
assert.ok(cardItem.template.content.includes("$t('Ready to pay')"));
assert.ok(!cardItem.template.content.includes('coveragePct +'));
assert.ok(!cardItem.template.content.includes('NPopover'));
const cardProps={item:{account_id:2,available:150}};
const cardScope={computed:vue.computed,ref:vue.ref,props:cardProps,accounts:bannerProps.accounts,pageState:{dates:{startDate:new Date('2026-10-01'),endDate:new Date('2026-10-31')}},format:date=>date.toISOString().slice(0,10)};
vm.createContext(cardScope);
vm.runInContext(ts.transpile('const card ='+cardItem.scriptSetup.content.split('const card =')[1].split('const onAssignBudget')[0]+'\n globalThis.result={cardDebt,apartado,coveragePct,brecha,coverageClass,brechaHref};'),cardScope);
assert.equal(cardScope.result.cardDebt.value,200);
assert.equal(cardScope.result.coveragePct.value,75);
assert.equal(cardScope.result.brecha.value,50);
assert.equal(cardScope.result.coverageClass.value,'bg-error');
assert.ok(cardScope.result.brechaHref.value.includes('filter[account_id]=2'));
const customTable = parse(fs.readFileSync('resources/js/Components/atoms/CustomTable.vue','utf8')).descriptor;
const tableCompiled = compileTemplate({source:customTable.template.content,filename:'CustomTable.vue',id:'custom-table'});
assert.deepEqual(tableCompiled.errors,[]);
const tableScope = {exports:{},require};
vm.createContext(tableScope);
vm.runInContext(ts.transpile(tableCompiled.code,{module:ts.ModuleKind.CommonJS}),tableScope);
const tableContext = {appearance:'finance',tableData:[{name:'Example'}],cols:[{name:'name',label:'Name'}],colStyle:()=>({}),getHeaderClass:()=>'',isLoading:false,$slots:{},$emit:()=>{}};
Promise.all(['finance','default'].map(appearance=>renderToString(vue.createSSRApp({render:tableScope.exports.render,data:()=>({...tableContext,appearance})})))).then(([finance,standard])=>{
 assert.ok(finance.includes('bg-base-lvl-2'));
 assert.ok(finance.includes('h-16'));
 assert.ok(finance.includes('Example'));
 assert.ok(standard.includes('py-4'));
 assert.ok(!standard.includes('h-16'));
}).catch(error=>{console.error(error);process.exitCode=1;});
const transactionTable = parse(fs.readFileSync('resources/js/domains/transactions/components/TransactionTable.vue','utf8')).descriptor;
assert.deepEqual(compileTemplate({source:transactionTable.template.content,filename:'TransactionTable.vue',id:'transactions'}).errors,[]);
assert.ok(transactionTable.template.content.includes('appearance="finance"'));
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
const transactionsLink = overview.template.content.match(/<Link[^>]*:href="route\('finance.transactions'\)"[^>]*>[\s\S]*?<\/Link>/)[0];
const linkCompiled = compileTemplate({source:transactionsLink,filename:'TransactionsLink.vue',id:'transactions-link'});
assert.deepEqual(linkCompiled.errors,[]);
const linkScope = {exports:{},require};
vm.createContext(linkScope);
vm.runInContext(ts.transpile(linkCompiled.code,{module:ts.ModuleKind.CommonJS}),linkScope);
const linkTree = linkScope.exports.render({route:name=>{assert.equal(name,'finance.transactions');return '/finance/transactions';}},[]);
assert.equal(linkTree.props.href,'/finance/transactions');
assert.ok(JSON.stringify(linkTree.children).includes('Ver todas las transacciones'));
assert.deepEqual(compileTemplate({source:overview.template.content,filename:'Overview.vue',id:'overview'}).errors,[]);
const accounts = [
 {id:1,name:'Bank DOP',currency_code:'DOP',balance:1000,archived:'0'},
 {id:2,name:'Banesco',bank_code:'Banesco',currency_code:'DOP',balance:-200,credit_limit:1000,credit_closing_day:15},
 {id:3,name:'Closed',currency_code:'DOP',balance:-9999,credit_limit:20000,credit_closing_day:20,closed_at:'2026-01-01'},
 {id:4,name:'USD bank',currency_code:'USD',balance:50},
];
let timerCallback, unmountCallback, clearedTimer;
const accountStore = {accounts, reorder:async()=>{}};
const overviewContext = {computed:vue.computed,ref:vue.ref,onMounted:()=>{},onUnmounted:callback=>{unmountCallback=callback;},setTimeout:(callback,delay)=>{assert.equal(delay,3000);timerCallback=callback;return 1;},clearTimeout:id=>{clearedTimer=id;},defineProps:()=>({accounts}),useAppContextStore:()=>({isMobile:false}),useAccountsStore:()=>accountStore};
vm.createContext(overviewContext);
vm.runInContext(ts.transpile(overview.scriptSetup.content.replace(/^import .*;\r?\n/gm,'') + '\n globalThis.result = { filtered, filter, search, summaries, reconciliation, groups, collapsedGroups, toggleGroup, saveOrder, orderMessage, isSavingOrder };'),overviewContext);
const o = overviewContext.result;
assert.equal(o.filter.value,'active');
assert.equal(o.filtered.value.length,3);
o.filter.value='all';
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
o.search.value='';
const headerTemplate = overview.template.content.match(/<h3>[\s\S]*?<\/h3>/)[0];
const headerCompiled = compileTemplate({source:headerTemplate,filename:'AccountGroupHeader.vue',id:'account-group-header'});
assert.deepEqual(headerCompiled.errors,[]);
const headerScope = {exports:{},require};
vm.createContext(headerScope);
vm.runInContext(ts.transpile(headerCompiled.code,{module:ts.ModuleKind.CommonJS}),headerScope);
const headerContext = {group:o.groups.value[0],groupIndex:0,collapsedGroups:o.collapsedGroups.value,toggleGroup:o.toggleGroup};
let headerTree = headerScope.exports.render(headerContext,[]);
assert.equal(headerTree.children[0].props['aria-expanded'],true);
assert.equal(headerTree.children[0].props['aria-controls'],'account-group-0');
const beforeCollapse = o.groups.value[0].accounts.map(a=>a.id).join(',');
headerTree.children[0].props.onClick();
headerTree = headerScope.exports.render(headerContext,[]);
assert.equal(headerTree.children[0].props['aria-expanded'],false);
assert.equal(o.groups.value[0].accounts.map(a=>a.id).join(','),beforeCollapse);
o.toggleGroup('Otras cuentas');
assert.equal(o.collapsedGroups.value['Otras cuentas'],true);
(async()=>{
 await o.saveOrder(accounts);
 assert.equal(o.orderMessage.value,'Orden guardado');
 assert.equal(o.isSavingOrder.value,false);
 timerCallback();
 assert.equal(o.orderMessage.value,'');
 await o.saveOrder(accounts);
 assert.equal(clearedTimer,1);
 accountStore.reorder=async()=>{throw new Error('Network failure');};
 await o.saveOrder(accounts);
 assert.equal(o.orderMessage.value,'No se pudo guardar el orden. Inténtalo de nuevo.');
 assert.equal(o.isSavingOrder.value,false);
 unmountCallback();
 assert.equal(clearedTimer,1);
})().catch(error=>{console.error(error);process.exitCode=1;});
headerTree.children[0].props.onClick();
assert.equal(o.collapsedGroups.value[headerContext.group.name],false);
assert.equal(o.collapsedGroups.value['Otras cuentas'],true);
JS;
        $process = new Process(['node'], dirname(__DIR__, 2));
        $process->setInput($script);
        $process->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
    }
}

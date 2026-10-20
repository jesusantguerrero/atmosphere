<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class SharedShoppingAvailabilityTest extends TestCase
{
    public function test_guest_availability_is_explicit_stable_and_recoverable_while_owner_cycle_is_preserved(): void
    {
        $script = <<<'JS'
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict'),ts=require('typescript'),vue=require('vue');
const {parse,compileTemplate}=require('@vue/compiler-sfc');
const file='resources/js/domains/shopping/components/ShoppingChatList.vue';
const descriptor=parse(fs.readFileSync(file,'utf8')).descriptor;
assert.deepEqual(compileTemplate({source:descriptor.template.content,filename:file,id:'shopping'}).errors,[]);
assert.ok(descriptor.template.content.indexOf('</h2>', descriptor.template.content.indexOf('id="shopping-history-title"')) < descriptor.template.content.indexOf('<Transition name="visit-header"'));
assert.ok(descriptor.styles[0].content.includes('scrollbar-button:vertical:increment'));assert.ok(descriptor.template.content.includes('visit-scroll'));
assert.ok(descriptor.template.content.includes('aria-label="Resumen de la visita"'));
assert.equal(descriptor.template.content.split('ref="historyDialog"')[1].split('</dialog>')[0].includes('text-4xl'),false);
assert.ok(descriptor.template.content.includes('min-h-[52px]'));
assert.equal(descriptor.template.content.includes('@click="reset"'),false);
assert.equal(descriptor.template.content.includes('{{ uncheckedCount }} sin revisar'),false);
assert.ok(descriptor.template.content.indexOf(':aria-pressed="onlyUnchecked"') < descriptor.template.content.indexOf('</header>'));
const shared=parse(fs.readFileSync('resources/js/Pages/ShoppingList/SharedShow.vue','utf8')).descriptor;
assert.deepEqual(compileTemplate({source:shared.template.content,filename:'SharedShow.vue',id:'shared'}).errors,[]);
const source=descriptor.scriptSetup.content.replace(/^import[\s\S]*?from\s+['"][^'"]+['"];\r?\n/gm,'');
function setup(inventoryMode) {
 const calls=[];let fail=false,gate=null;
 const props={inventoryMode,plan:{name:'Diana',stages:[{id:1,name:'SHOPPING_LIST',items:[{id:1,title:'Leche',state:'pending',is_done:false,order:1},{id:2,title:'Pañales',state:'skip',is_done:false,order:2}]},{id:2,name:'Empty',items:[]}]},endpoints:{cycle:id=>`/toggle/${id}`,add:'/add',reset:'/reset'}};
 const context={...vue,defineProps:()=>vue.reactive(props),defineEmits:()=>()=>{},useI18n:()=>({t:value=>value}),onMounted:()=>{},onBeforeUnmount:()=>{},axios:{post:async(url,data)=>{calls.push({url,data});if(gate)await gate;if(fail)throw Error('offline');return {data:{id:3,title:data.title,state:'pending'}};}}};
 vm.createContext(context);vm.runInContext(ts.transpile(source+'\nglobalThis.list={stages,allItems,visibleSections,cycleItem,counts,inventorySearch,composer,submitComposer,saveError,updatingItems,existingProduct,reviewDialog,openReview,reviewError,productCategory,productCategoryName,isReviewed,uncheckedCount,openMoveCategory,categoryDialog,movingItem,movingCategory,onlyUnchecked,review,undoMark,lastMark,shoppingSession,historyDialog,openHistory,selectedVisit,viewVisit,backToHistory,visitGroups,formatVisitAmount,visitAmountElement,headerShowsAmount,handleHistoryScroll};',{target:ts.ScriptTarget.ES2020}),context);
 return {list:context.list,calls,setFail:value=>fail=value,setGate:value=>gate=value};
}
(async()=>{
 const guest=setup(true),g=guest.list;
 assert.equal(g.uncheckedCount.value,2);g.review.value={reviewed_ids:[1]};g.onlyUnchecked.value=true;assert.equal(g.visibleSections.value[0].items.length,1);assert.equal(g.visibleSections.value[0].items[0].id,2);g.onlyUnchecked.value=false;g.review.value=null;assert.equal(g.isReviewed(g.allItems.value[0]),false);
 let categoryOpened=false;g.categoryDialog.value={showModal:()=>categoryOpened=true};g.openMoveCategory(g.allItems.value[0],1);assert.equal(categoryOpened,true);assert.equal(g.movingCategory.value,1);assert.equal(g.movingItem.value.id,1);
 let opened=false;g.reviewDialog.value={showModal:()=>opened=true};g.reviewError.value='old';g.openReview();assert.equal(opened,true);assert.equal(g.reviewError.value,'');
 assert.ok(descriptor.template.content.includes('Confirmar lista'));assert.ok(descriptor.template.content.includes('Agregar nota para esta compra'));
 g.composer.value='  LECHE ';assert.equal(g.existingProduct.value.id,1);await g.submitComposer();assert.equal(guest.calls.length,0);g.composer.value='';
 assert.equal(g.visibleSections.value.length,1);assert.equal(g.visibleSections.value[0].name,'Otros productos');
 await g.cycleItem(g.allItems.value[0],true);assert.equal(guest.calls[0].data.state,'skip');assert.equal(g.counts.value.pending,0);
 assert.equal(g.visibleSections.value[0].items[0].id,1);
 await g.cycleItem(g.allItems.value[0],false);assert.equal(guest.calls[1].data.state,'pending');
 guest.setFail(true);await g.cycleItem(g.allItems.value[0],true);assert.equal(g.allItems.value[0].state,'pending');assert.ok(g.saveError.value);assert.equal(g.updatingItems.value.length,0);
 g.composer.value='Fruta';await g.submitComposer();assert.equal(g.composer.value,'Fruta');assert.equal(g.allItems.value.length,2);
 guest.setFail(false);g.productCategory.value=2;await g.submitComposer();assert.equal(guest.calls.at(-1).data.stage_id,2);assert.equal(g.composer.value,'');assert.equal(g.allItems.value.length,3);
 g.composer.value='paña';assert.equal(g.visibleSections.value[0].items.length,1);assert.equal(g.visibleSections.value[0].items[0].title,'Pañales');
 const categorized=setup(true);categorized.list.stages.value.push({id:3,name:'Frutas y vegetales',items:[{id:5,title:'Sandía',state:'pending',is_done:false,order:1}]});
 assert.equal(categorized.list.visibleSections.value.length,2);categorized.list.composer.value='sand';assert.equal(categorized.list.visibleSections.value[0].name,'Frutas y vegetales');assert.equal(categorized.list.visibleSections.value[0].items[0].title,'Sandía');
 const rapid=setup(true);let release;rapid.setGate(new Promise(resolve=>release=resolve));
 const first=rapid.list.cycleItem(rapid.list.allItems.value[0],true);
 await rapid.list.cycleItem(rapid.list.allItems.value[0],false);
 assert.equal(rapid.list.allItems.value[0].state,'pending');assert.equal(rapid.calls.length,1);
 rapid.setGate(null);release();await first;
 assert.equal(rapid.calls.length,2);assert.equal(rapid.calls[1].data.state,'pending');assert.equal(rapid.list.updatingItems.value.length,0);
 const history=setup(false);let historyOpened=false;history.list.historyDialog.value={showModal:()=>historyOpened=true};history.list.openHistory();assert.equal(historyOpened,true);history.list.viewVisit({id:'visit',total:'50.00'});history.list.visitAmountElement.value={getBoundingClientRect:()=>({bottom:40})};history.list.handleHistoryScroll({currentTarget:{getBoundingClientRect:()=>({top:50})}});assert.equal(history.list.headerShowsAmount.value,true);history.list.visitAmountElement.value={getBoundingClientRect:()=>({bottom:80})};history.list.handleHistoryScroll({currentTarget:{getBoundingClientRect:()=>({top:50})}});assert.equal(history.list.headerShowsAmount.value,false);assert.equal(history.list.selectedVisit.value.id,'visit');history.list.selectedVisit.value={purchased_items:[{id:1,title:'Leche',category:'SHOPPING_LIST'},{id:2,title:'Pan',category:'Panadería'}]};assert.equal(history.list.visitGroups.value.length,2);assert.equal(history.list.visitGroups.value[0].name,'Otros productos');assert.equal(history.list.formatVisitAmount('5000'),'5,000.00');history.list.backToHistory();assert.equal(history.list.selectedVisit.value,null);history.list.viewVisit({id:'visit'});history.list.openHistory();assert.equal(history.list.selectedVisit.value,null);assert.ok(descriptor.template.content.includes('Ver historial de compras'));
 const owner=setup(false);for(const state of ['buy','skip','pending']){await owner.list.cycleItem(owner.list.allItems.value[0]);assert.equal(owner.list.allItems.value[0].state,state);}
 const sessionOwner=setup(false);sessionOwner.list.shoppingSession.value={id:'session',items:[{id:1}],purchased_ids:[]};assert.equal(sessionOwner.list.visibleSections.value[0].items.length,1);await sessionOwner.list.cycleItem(sessionOwner.list.allItems.value[0]);assert.equal(sessionOwner.list.allItems.value[0].state,'buy');await sessionOwner.list.cycleItem(sessionOwner.list.allItems.value[0]);assert.equal(sessionOwner.list.allItems.value[0].state,'pending');
 const sharedContext={defineProps:()=>({token:'test-token'})};vm.createContext(sharedContext);
 vm.runInContext(ts.transpile(shared.scriptSetup.content.replace(/^import[^\n]+\r?\n/gm,'')+'\nglobalThis.endpoints=endpoints;',{target:ts.ScriptTarget.ES2020}),sharedContext);
 const undo=setup(true);undo.list.lastMark.value={id:1,title:'Leche',state:'pending',reviewed:false};await undo.list.undoMark();assert.equal(undo.calls[0].data.reviewed,false);assert.equal(undo.list.lastMark.value,null);
 assert.equal(sharedContext.endpoints.destroy,undefined);
})().catch(error=>{console.error(error);process.exitCode=1;});
JS;
        $process = new Process(['node'], dirname(__DIR__, 2));
        $process->setInput($script)->setTimeout(30)->run();
        $this->assertTrue($process->isSuccessful(), $process->getOutput().$process->getErrorOutput());
    }
}

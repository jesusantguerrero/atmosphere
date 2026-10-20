<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ProfileWorkspacePreviewTest extends TestCase
{
    public function test_profile_boards_keep_the_same_pages_across_views_and_tasks_stay_connected(): void
    {
        $script = <<<'JS'
const fs=require('node:fs');
const vm=require('node:vm');
const assert=require('node:assert/strict');
const ts=require('typescript');
const vue=require('vue');
const {parse,compileTemplate}=require('@vue/compiler-sfc');
for(const file of ['resources/js/Pages/LogerProfile/WorkspacePreview.vue','resources/js/domains/logerProfile/ProfileGlyph.vue','resources/js/Pages/LogerProfile/Overview.vue','resources/js/Pages/LogerProfile/Index.vue','resources/js/Pages/LogerProfile/ProfileView.vue','resources/js/Components/templates/ProfilesPillarNav.vue','resources/js/Components/templates/AppLayout.vue','resources/js/Components/AppUserMenu.vue']) {
 const component=parse(fs.readFileSync(file,'utf8')).descriptor;
 assert.deepEqual(compileTemplate({source:component.template.content,filename:file,id:file}).errors,[]);
}
const navSource=fs.readFileSync('resources/js/domains/app/index.ts','utf8').replace(/^import[^\n]+\r?\n/gm,'').replace(/^export \* from[^\n]+\r?\n/gm,'');
const nav={exports:{},Link:{},IconPlus:{},IconTransferVue:{},cloneDeep:value=>value};
vm.runInNewContext(ts.transpile(navSource,{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2020}),nav);
const menus=nav.exports.useAppMenu(value=>value,[{name:'housing',enabled:true},{name:'profiles',enabled:true},{name:'meals',enabled:true}]);
const profilePillar=menus.appMenu.find(item=>item.name==='profiles');
assert.ok(profilePillar);assert.equal(profilePillar.to,'/loger-profiles/overview');assert.ok(!menus.appMenu.some(item=>item.to==='/admin'));
for(const path of ['/loger-profiles','/loger-profiles/20/preview','/loger-profiles/overview']) {
 assert.equal(menus.appMenu.filter(item=>item.isActiveFunction?.(item.to,path)).length,1);
 assert.equal(profilePillar.isActiveFunction('',path),true);
 assert.equal(menus.mobileMenu.find(item=>item.name==='Tasks').activeMatch.test(path),false);
}
assert.equal(profilePillar.isActiveFunction('','/housing'),false);
assert.equal(profilePillar.isActiveFunction('','/calendar'),false);
const sectionSource=fs.readFileSync('resources/js/domains/app/menus.ts','utf8');
const sections={exports:{}};vm.runInNewContext(ts.transpile(sectionSource,{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2020}),sections);
assert.equal(sections.exports.getSectionMenu(sections.exports.MODULES.PROFILES).length,2);
const model={exports:{}};
vm.runInNewContext(ts.transpile(fs.readFileSync('resources/js/domains/logerProfile/profilePreview.ts','utf8'),{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2020}),model);
const {pagesOf,pendingPages}=model.exports;
const fixture={boards:[{id:'11',name:'Salud',description:'',color:'#888',view:'notebook',stages:[{id:'12',name:'Terapia',items:[{id:'13',title:'Persisted note',body:'From backend',kind:'task',done:false}]}]}],tasks:[],categories:[],transactions:[],occurrences:[],purchases:[],connections:{finance:false,shopping:false,tracking:false},availableLinks:[],month:'2026-10',categoryGroup:''};
assert.equal(pendingPages(fixture.boards).length,1);
const descriptor=parse(fs.readFileSync('resources/js/Pages/LogerProfile/WorkspacePreview.vue','utf8')).descriptor;
const script=descriptor.scriptSetup.content.replace(/^import[\s\S]*?from\s+['"][^'"]+['"];\r?\n/gm,'');
let request,fail=false;
const context={...vue,pagesOf,pendingPages,structuredClone,Intl,Date,route:()=>'/loger-profiles/1/preview',axios:{request:async config=>{request=config;if(fail)throw Error('offline'); const next=structuredClone(fixture);if(config.data.body)next.boards[0].stages[0].items[0].body=config.data.body;return {data:{workspace:next}};}},defineProps:()=>({profile:{id:1,name:'Example'},workspace:vue.reactive(fixture)})};
vm.createContext(context);
vm.runInContext(ts.transpile(script+'\nglobalThis.preview={boards,openBoard,openNotebook,backFromNotebook,openPage,view,selectedStage,selectedPage,pending,savePage,saveStatus,error};',{target:ts.ScriptTarget.ES2020}),context);
(async()=>{
 const p=context.preview;
 p.openBoard(p.boards.value[0]);
 assert.equal(p.selectedStage.value,undefined);assert.equal(p.selectedPage.value,undefined);
 p.openNotebook(p.boards.value[0].stages[0]);
 assert.equal(p.selectedStage.value.name,'Terapia');
 assert.equal(p.selectedPage.value.body,'From backend');
 p.selectedPage.value.body='Edited note';
 await p.savePage(p.selectedPage.value);
 assert.equal(request.method,'patch');assert.equal(request.url,'/loger-profiles/1/workspace/items/13');assert.equal(request.data.body,'Edited note');
 assert.equal(p.selectedPage.value.body,'Edited note');assert.equal(p.saveStatus.value,'Guardado');
 fail=true;p.selectedPage.value.body='Unsaved draft';await p.savePage(p.selectedPage.value);
 assert.equal(p.selectedPage.value.body,'Unsaved draft');assert.equal(p.saveStatus.value,'Cambios sin guardar');assert.ok(p.error.value);
 p.backFromNotebook();assert.equal(p.selectedStage.value,undefined);assert.equal(p.selectedPage.value,undefined);
 p.openPage(p.boards.value[0],p.boards.value[0].stages[0].items[0]);assert.equal(p.selectedStage.value.id,'12');assert.equal(p.selectedPage.value.id,'13');
})().catch(error=>{console.error(error);process.exitCode=1;});
JS;
        $process = new Process(['node'], dirname(__DIR__, 2));
        $process->setInput($script)->setTimeout(30)->run();
        $this->assertTrue($process->isSuccessful(), $process->getOutput().$process->getErrorOutput());
    }
}

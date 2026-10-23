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
for(const file of ['resources/js/Components/TaskProfileLinks.vue','resources/js/Components/board/ItemModal.vue','resources/js/Components/board/BoardSection.vue','resources/js/Components/board/views/FamilyView.vue','resources/js/Components/NotebookLiveEditor.vue','resources/js/Components/templates/AppShell.vue','resources/js/Components/molecules/AppNotificationBell.vue','resources/js/Components/NotebookInline.vue','resources/js/Components/NotebookReading.vue','resources/js/Pages/LogerProfile/WorkspacePreview.vue','resources/js/domains/logerProfile/ProfileGlyph.vue','resources/js/Pages/LogerProfile/Overview.vue','resources/js/Pages/LogerProfile/Index.vue','resources/js/Pages/LogerProfile/ProfileView.vue','resources/js/Components/templates/ProfilesPillarNav.vue','resources/js/Components/templates/AppLayout.vue','resources/js/Components/AppUserMenu.vue']) {
 const component=parse(fs.readFileSync(file,'utf8')).descriptor;
 assert.deepEqual(compileTemplate({source:component.template.content,filename:file,id:file}).errors,[]);
}
const markdown={exports:{}};
vm.runInNewContext(ts.transpile(fs.readFileSync('resources/js/domains/logerProfile/notebookMarkdown.ts','utf8'),{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2020}),markdown);
const blocks=markdown.exports.markdownBlocks('# Report\n\n3. First\n4. Second\n\n- Item\n\n<script>alert(1)</script>');
assert.equal(blocks[0].tag,'h1');assert.equal(blocks[1].start,3);assert.equal(blocks[1].items.length,2);assert.equal(blocks[3].text,'<script>alert(1)</script>');
assert.equal(markdown.exports.markdownInline('**bold** and *italic*')[0].tag,'strong');
assert.equal(markdown.exports.markdownInline('[[Próxima sesión]]')[0].tag,'link');assert.equal(markdown.exports.markdownInline('[[Próxima sesión]]')[0].text,'Próxima sesión');
assert.ok(!fs.readFileSync('resources/js/Pages/LogerProfile/WorkspacePreview.vue','utf8').includes('Esta página pertenece a'));
const workspaceSource=fs.readFileSync('resources/js/Pages/LogerProfile/WorkspacePreview.vue','utf8');
assert.ok(workspaceSource.includes(`v-if="selectedPage.kind === 'event' && editingPage"`));
assert.match(workspaceSource,/selectedPage.kind === 'event'\s*&&?\s*!editingPage|selectedPage.kind === 'event' && !editingPage/);
assert.ok(workspaceSource.includes(':datetime="selectedPage.when"'));
assert.match(workspaceSource,/v-if="!selectedStage"\s+aria-label="Secciones del perfil"/);
assert.ok(workspaceSource.includes('flex h-6 items-center justify-end'));
assert.ok(workspaceSource.includes('lg:grid-rows-[auto_1fr_auto]'));
assert.ok(workspaceSource.includes('notebook: "Cuaderno"'));
assert.ok(workspaceSource.includes(':aria-pressed="editingPage"'));
assert.ok(workspaceSource.includes('Modo concentración'));
assert.ok(workspaceSource.includes('v-if="statisticsVisible"'));
assert.ok(fs.readFileSync('resources/js/Components/templates/AppShell.vue','utf8').includes('.focus-mode .app-header'));
assert.ok(workspaceSource.includes('role="radiogroup"'));
assert.ok(workspaceSource.includes(':aria-checked="view === key"'));
const editorDescriptor=parse(fs.readFileSync('resources/js/Components/NotebookLiveEditor.vue','utf8')).descriptor;
const editorScript=editorDescriptor.scriptSetup.content.replace(/^import[\s\S]*?from\s+['"][^'"]+['"];\r?\n/gm,'');
let editedMarkdown;
const editorContext={...vue,defineProps:()=>({modelValue:'# Title\n\n**First** paragraph\n\n- Item',disabled:false}),defineEmits:()=>((event,value)=>{editedMarkdown=value;})};
vm.createContext(editorContext);vm.runInContext(ts.transpile(editorScript+'\nglobalThis.editorApi={pieces,active,update,finishBlock,blocks};',{target:ts.ScriptTarget.ES2020}),editorContext);
const editorApi=editorContext.editorApi;assert.equal(editorApi.blocks.value.length,3);editorApi.active.value=2;editorApi.update({target:{value:'Changed\n\nNew paragraph'}});assert.equal(editedMarkdown,'# Title\n\nChanged\n\nNew paragraph\n\n- Item');editorApi.finishBlock();assert.equal(editorApi.blocks.value.length,4);assert.equal(editorApi.active.value,null);
const indexDescriptor=parse(fs.readFileSync('resources/js/Pages/LogerProfile/Index.vue','utf8')).descriptor;
const indexScript=indexDescriptor.scriptSetup.content.replace(/^import[\s\S]*?from\s+['"][^'"]+['"];\r?\n/gm,'');
for(const count of [0,1,4,20,100]) {
 const data=Array.from({length:Math.min(count,24)},(_,i)=>({id:i+1,name:'Profile '+i,personal_user_id:i===0?12:null}));
 let requestCount=0;
 const indexContext={...vue,onMounted:()=>{},onBeforeUnmount:()=>{},usePage:()=>({props:{auth:{user:{id:12}}}}),defineProps:()=>({profiles:{data,total:count,current_page:1,last_page:Math.ceil(count/24)},profileCount:count,filters:{search:'',sort:'name'}}),router:{get:()=>{requestCount++;},reload:()=>{}},route:()=>'/loger-profiles'};
 vm.createContext(indexContext);vm.runInContext(ts.transpile(indexScript+'\nglobalThis.indexApi={visibleProfiles,showTools,search,sort,searchProfiles,clearSearch};',{target:ts.ScriptTarget.ES2020}),indexContext);
 const api=indexContext.indexApi;assert.equal(api.visibleProfiles.value.length,Math.min(count,24));assert.equal(api.showTools.value,count>=8);api.search.value='missing';
 if(count<=24){assert.equal(api.visibleProfiles.value.length,0);api.searchProfiles();assert.equal(requestCount,0);}else{api.searchProfiles();assert.equal(requestCount,1);}
 api.clearSearch();assert.equal(api.visibleProfiles.value.length,Math.min(count,24));
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
let request,fail=false;const mounts=[];let replacedUrl;
const context={...vue,onMounted:callback=>mounts.push(callback),onBeforeUnmount:()=>{},URL,URLSearchParams,window:{location:{search:"?board=11&notebook=12&page=13",href:"http://localhost/loger-profiles/1?other=kept"},history:{state:{},replaceState:(state,title,url)=>{replacedUrl=url;}},addEventListener:()=>{}},pagesOf,pendingPages,structuredClone,Intl,Date,route:()=>'/loger-profiles/1/preview',axios:{request:async config=>{request=config;if(fail)throw Error('offline'); const next=structuredClone(fixture);if(config.data.body)next.boards[0].stages[0].items[0].body=config.data.body;return {data:{workspace:next}};}},defineProps:()=>({profile:{id:1,name:'Example'},workspace:vue.reactive(fixture)})};
vm.createContext(context);
vm.runInContext(ts.transpile(script+'\nglobalThis.preview={boards,openBoard,openNotebook,backFromNotebook,openPage,view,selectedStage,selectedPage,pending,savePage,saveStatus,error,editingPage,selectPage,pageSearch,visiblePages,finishEditing,saveShortcut,toggleReading,indexVisible,pageStats,hasUnsavedPage};',{target:ts.ScriptTarget.ES2020}),context);
(async()=>{
 const p=context.preview;
 mounts.forEach(callback=>callback());assert.equal(p.selectedPage.value.id,'13');assert.equal(p.hasUnsavedPage.value,false);assert.equal(p.pageStats.value.words,2);
 p.openBoard(p.boards.value[0]);
 assert.equal(p.selectedStage.value,undefined);assert.equal(p.selectedPage.value,undefined);
 p.openNotebook(p.boards.value[0].stages[0]);
 assert.equal(p.selectedStage.value.name,'Terapia');
 assert.equal(p.selectedPage.value.body,'From backend');assert.equal(p.editingPage.value,false);
 await p.toggleReading();assert.equal(p.editingPage.value,true);await p.toggleReading();assert.equal(p.editingPage.value,false);assert.equal(p.indexVisible.value,true);p.indexVisible.value=false;assert.equal(p.selectedPage.value.body,'From backend');
 p.pageSearch.value='backend';assert.equal(p.visiblePages.value.length,1);p.pageSearch.value='absent';assert.equal(p.visiblePages.value.length,0);p.pageSearch.value='';
 p.editingPage.value=true;let prevented=false;p.saveShortcut({ctrlKey:true,key:'s',preventDefault:()=>{prevented=true;}});await new Promise(resolve=>setTimeout(resolve,0));assert.equal(prevented,true);assert.equal(p.editingPage.value,false);
 p.selectedPage.value.body='Edited note';assert.equal(p.hasUnsavedPage.value,true);
 await p.savePage(p.selectedPage.value);
 assert.equal(request.method,'patch');assert.equal(request.url,'/loger-profiles/1/workspace/items/13');assert.equal(request.data.body,'Edited note');
 assert.equal(p.selectedPage.value.body,'Edited note');assert.equal(p.saveStatus.value,'Guardado');assert.equal(p.hasUnsavedPage.value,false);await vue.nextTick();assert.equal(replacedUrl.searchParams.get('page'),'13');assert.equal(replacedUrl.searchParams.get('other'),'kept');
 fail=true;p.selectedPage.value.body='Unsaved draft';await p.savePage(p.selectedPage.value);
 assert.equal(p.selectedPage.value.body,'Unsaved draft');p.editingPage.value=true;await p.finishEditing();assert.equal(p.editingPage.value,true);assert.equal(p.saveStatus.value,'Cambios sin guardar');assert.ok(p.error.value);
 p.backFromNotebook();assert.equal(p.selectedStage.value,undefined);assert.equal(p.selectedPage.value,undefined);
 p.openPage(p.boards.value[0],p.boards.value[0].stages[0].items[0]);assert.equal(p.selectedStage.value.id,'12');assert.equal(p.selectedPage.value.id,'13');
})().catch(error=>{console.error(error);process.exitCode=1;});
JS;
        $process = new Process(['node'], dirname(__DIR__, 2));
        $process->setInput($script)->setTimeout(30)->run();
        $this->assertTrue($process->isSuccessful(), $process->getOutput().$process->getErrorOutput());
    }
}

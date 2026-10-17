<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class CategoryManagerPresentationTest extends TestCase
{
    public function test_preview_requires_confirmation_ignores_stale_responses_and_preserves_input_on_failure(): void
    {
        $script = <<<'JS'
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const ts = require('typescript');
const vue = require('vue');
const {parse, compileTemplate} = require('@vue/compiler-sfc');
const descriptor = parse(fs.readFileSync('resources/js/Pages/Finance/CategoryManager.vue', 'utf8')).descriptor;
assert.deepEqual(compileTemplate({source:descriptor.template.content, filename:'CategoryManager.vue', id:'manager'}).errors, []);
assert.match(descriptor.template.content, /<main\s+class="[^"]*\bmt-12\b/, 'The manager must reserve space for the fixed finance navigation above its heading and actions.');
const budget = parse(fs.readFileSync('resources/js/Pages/Finance/Budget.vue', 'utf8')).descriptor;
assert.deepEqual(compileTemplate({source:budget.template.content, filename:'Budget.vue', id:'budget'}).errors, []);
const props = vue.reactive({managedCategories:[
 {id:10, name:'Daily', parent_id:null, protected:false},
 {id:1, name:'Dining', parent_id:10, protected:false},
 {id:2, name:'Food', parent_id:10, protected:false},
], merges:[]});
const calls = [];
let resolvePreview;
const context = { ...vue, defineProps:()=>props, router:{reload:()=>{}}, route:(name,id)=>`${name}/${id??''}`, axios:{post:(...args)=>{calls.push(args);return new Promise(resolve=>resolvePreview=resolve)},patch:async()=>{throw {response:{data:{errors:{name:['Save failed']}}}}}} };
vm.createContext(context);
const source = descriptor.scriptSetup.content.replace(/^import .*;\r?\n/gm,'');
vm.runInContext(ts.transpile(source+'\nglobalThis.result={selectedId, creating, startCreate, name, save, busy, error, targetId, preview, confirmed, showPreview, merge, goalChoice, previewBlockers};',{target:ts.ScriptTarget.ES2020}),context);
(async()=>{
 const r=context.result;
 r.selectedId.value=1;
 r.startCreate(true);
 await vue.nextTick();
 assert.equal(r.creating.value,true);
 r.selectedId.value=1;
 r.targetId.value=2;
 await vue.nextTick();
 const pending=r.showPreview();
 r.selectedId.value=2;
 resolvePreview({data:{source:'Dining',target:'Food',blockers:[],token:'abc'}});
 await pending;
 assert.equal(r.preview.value,null);
 r.selectedId.value=1;
 r.name.value='Keep my input';
 await r.save();
 assert.equal(r.name.value,'Keep my input');
 assert.equal(r.error.value,'Save failed');
 r.preview.value={blockers:[],token:'abc'};
 const count=calls.length;
 await r.merge();
 assert.equal(calls.length,count);
 r.confirmed.value=true;
 const merge=r.merge();
 assert.equal(calls.length,count+1);
 assert.equal(calls.at(-1)[0],'category-manager.merge/1');
 await r.merge();
 assert.equal(calls.length,count+1);
 resolvePreview({data:{id:1}});
 await merge;
 assert.equal(r.busy.value,false);
})().catch(error=>{console.error(error);process.exitCode=1;});
JS;
        $process = new Process(['node'], dirname(__DIR__, 2));
        $process->setInput($script)->setTimeout(30)->run();
        $this->assertTrue($process->isSuccessful(), $process->getOutput().$process->getErrorOutput());
    }
}

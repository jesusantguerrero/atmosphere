<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class OccurrenceModalPresentationTest extends TestCase
{
    public function test_modal_state_and_preview_use_current_edits_without_mutating_saved_data(): void
    {
        $script = <<<'JS'
const fs=require('node:fs'), vm=require('node:vm'), assert=require('node:assert/strict');
const ts=require('typescript'), vue=require('vue');
const {parse,compileTemplate}=require('@vue/compiler-sfc');
const helpers={exports:{}}; vm.createContext(helpers);
vm.runInContext(ts.transpile(fs.readFileSync('resources/js/domains/housing/occurrenceForm.ts','utf8')),helpers);
const {occurrenceFormData,occurrenceRuleFields,hasOccurrenceRule}=helpers.exports;
const saved={id:73,name:'Gasolina',notify_on_avg:false,notify_on_last_count:0,is_active:'0',conditions:{description:[{operator:'contains',value:'gasolina'}],category_id:[7]}};
const edited=occurrenceFormData(saved);
assert.equal(edited.notify_on_avg,false); assert.equal(edited.notify_on_last_count,false); assert.equal(edited.is_active,false);
edited.conditions.description[0].value='groceries'; edited.conditions.category_id.push(9);
assert.equal(saved.conditions.description[0].value,'gasolina'); assert.equal(saved.conditions.category_id.length,1);
assert.equal(occurrenceFormData().id,null); assert.equal(occurrenceFormData().notify_on_avg,true);
assert.equal(occurrenceFormData(null).id,null);
assert.equal(hasOccurrenceRule({description:[{operator:'contains',value:'  '}],category_id:[]}),false);
assert.equal(hasOccurrenceRule({description:[],category_id:[7]}),true);
const rich=occurrenceFormData({conditions:{groups:[{match:'any',payee_id:[4]}],exclude:{account_id:[9]}}});
assert.equal(rich.conditions.match,'all');assert.equal(hasOccurrenceRule(rich.conditions),true);
assert.equal(hasOccurrenceRule({exclude:{payee_id:[4]}}),false);
assert.equal(hasOccurrenceRule({account_id:[9]}),true);assert.equal(hasOccurrenceRule({payee_id:[4]}),true);
const clone=occurrenceFormData(rich);clone.conditions.groups[0].payee_id.push(8);clone.conditions.exclude.account_id.push(10);
assert.equal(rich.conditions.groups[0].payee_id.length,1);assert.equal(rich.conditions.exclude.account_id.length,1);
const files=['resources/js/Components/OccurrenceCheckModal.vue','resources/js/Components/OccurrencePreview.vue','resources/js/Components/OccurrenceRuleFields.vue'];
const parts=files.map(filename=>{const {descriptor}=parse(fs.readFileSync(filename,'utf8'));assert.deepEqual(compileTemplate({source:descriptor.template.content,filename,id:filename}).errors,[]);return descriptor;});
const props={show:true,formData:saved}, watches=[], emitted=[], submitted=[];
const modal={defineProps:()=>props,defineEmits:()=>(...args)=>emitted.push(args),nextTick:()=>{},computed:vue.computed,ref:vue.ref,inject:()=>[],unref:vue.unref,occurrenceFormData,occurrenceRuleFields,hasOccurrenceRule,route:(...args)=>args.join('/'),watch:(source,callback,options)=>{watches.push(callback);if(options?.immediate)callback();},useForm:initial=>({...initial,processing:false,errors:{},clearErrors(){this.errors={};},data(){return this;},put:(...args)=>submitted.push(['put',...args]),post:(...args)=>submitted.push(['post',...args])})};
vm.createContext(modal);
vm.runInContext(ts.transpile(parts[0].scriptSetup.content.replace(/^import .*;\r?\n/gm,'')+'\n globalThis.result={form,activeTab,submit,addGroup};'),modal);
assert.equal(modal.result.form.is_active,false);
modal.result.addGroup();assert.equal(modal.result.form.conditions.groups[0].match,'any');
assert.equal(modal.result.form.conditions.groups[0].payee_id.length,0);
modal.result.activeTab.value='preview'; props.formData={}; watches[0]();
assert.equal(modal.result.form.id,null); assert.equal(modal.result.form.name,''); assert.equal(modal.result.activeTab.value,'information');
modal.result.form.name='  Groceries  '; modal.result.submit(); assert.equal(submitted[0][0],'post'); assert.equal(modal.result.form.name,'Groceries');
modal.result.form.processing=true; modal.result.submit(); assert.equal(submitted.length,1);
const pending=[];const calls=[];const previewProps={conditions:edited.conditions};
const scope={defineProps:()=>previewProps,ref:vue.ref,computed:vue.computed,watch:()=>{},useI18n:()=>({t:key=>key}),hasOccurrenceRule,axios:{post:(url,body)=>{calls.push({url,body:JSON.parse(JSON.stringify(body))});return new Promise((resolve,reject)=>pending.push({resolve,reject}));}}};
vm.createContext(scope); vm.runInContext(ts.transpile(parts[1].scriptSetup.content.replace(/^import .*;\r?\n/gm,'')+'\n globalThis.result={preview,transactions,loading,error};'),scope);
(async()=>{
const first=scope.result.preview(); assert.equal(scope.result.loading.value,true);
previewProps.conditions={description:[],category_id:[9]}; const second=scope.result.preview();
pending[1].resolve({data:[{id:2,date:'2026-10-23'}]}); await second;
pending[0].resolve({data:[{id:1,date:'2024-01-01'}]}); await first;
assert.equal(scope.result.transactions.value[0].id,2); assert.equal(calls[1].body.conditions.category_id[0],9);
assert.equal(calls[0].url,'/housing/occurrences/preview');
const failing=scope.result.preview(); pending[2].reject(new Error('Network')); await failing;
assert.ok(scope.result.error.value); assert.equal(scope.result.loading.value,false);
previewProps.conditions={description:[],category_id:[]}; await scope.result.preview();assert.equal(calls.length,3);assert.equal(scope.result.transactions.value.length,0);
})().catch(error=>{console.error(error);process.exitCode=1;});
JS;
        $process = new Process(['node', '-e', $script], dirname(__DIR__, 2));
        $process->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
    }
}

import{i as q,g as F,Z as G,E as N}from"./atmosphere-ui.588c294c.js";import{s as S,B,x as U,ac as E,l as L,o as s,g,b as p,d,k as R,c as l,t as y,a as c,i as $,e as H,n as W,a5 as Z,q as V,u as J,f as D,F as C,h as M,a6 as x}from"./app.cefd7668.js";import{e as K,a as Q,_ as X}from"./AppLayout.cd77f56f.js";import{_ as Y}from"./Modal.a2277346.js";import{_ as ee}from"./MealSection.85c24a54.js";import{_ as k}from"./_plugin-vue_export-helper.cdc0426e.js";import{_ as te,a as se}from"./MealSectionNav.948f5b10.js";import{_ as I}from"./LogerButton.0789417e.js";import{s as O}from"./index.fe789daa.js";import{a6 as T}from"./index.de789d8e.js";import"./LogerInput.bec0734e.js";/* empty css                                              */import"./SectionTitle.23b660a7.js";import"./ImportResourceModal.ab0a2563.js";import"./SectionNav.d2e4d6cd.js";const oe={emits:["close"],components:{Modal:Y,AtField:q,AtInput:F,AtButton:G,Meal:ee},props:{show:{default:!1},maxWidth:{default:"2xl"},closeable:{default:!0},meals:{type:Array,default(){return[]}},date:{default:new Date},title:{type:String}},setup(o,{emit:n}){const r=S({meal:null}),{show:e}=B(o),_=()=>{n("close")};U(e,()=>{e.value&&u()},{immediate:!0});const u=()=>{r.meal=null,E.get("/meals-random").then(({data:f})=>{r.meal=f})};return{...B(r),submit:u,close:_}}},ae={class:"px-4 py-5 text-center text-white bg-primary sm:p-6 sm:pb-4"},le={class:"justify-center sm:flex sm:items-start"},ne={class:"mt-3 text-center sm:mt-0 sm:ml-4"},re={class:"w-full text-4xl font-bold text-white"},ie=$(" The meal is: "),de={class:"mt-5 mb-5"},me={key:0,class:"mt-5 text-2xl"},ce={key:1,class:"mt-5 text-2xl"},ue={class:"mt-5"},pe=$(" Random ");function _e(o,n,r,e,_,u){const f=L("at-button"),m=L("modal");return s(),g(m,{show:r.show,"max-width":r.maxWidth,closeable:r.closeable,onClose:e.close},{default:p(()=>[d("div",ae,[d("div",le,[d("div",ne,[d("h3",re,[R(o.$slots,"title",{},()=>[ie])]),d("div",de,[R(o.$slots,"content",{},()=>[o.meal?(s(),l("div",me,y(o.meal.name),1)):(s(),l("div",ce," loading ... "))]),d("div",ue,[c(f,{class:"text-gray-700 bg-base-lvl-1",disabled:!o.meal,onClick:n[0]||(n[0]=h=>e.submit())},{default:p(()=>[pe]),_:1},8,["disabled"])])])])])])]),_:3},8,["show","max-width","closeable","onClose"])}const fe=k(oe,[["render",_e]]),be={name:"IcSharpClose"},ve={width:"1em",height:"1em",viewBox:"0 0 24 24"},ye=d("path",{fill:"currentColor",d:"M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41z"},null,-1),$e=[ye];function he(o,n,r,e,_,u){return s(),l("svg",ve,$e)}const xe=k(be,[["render",he]]),ge={name:"RiHeartFill"},we={width:"1em",height:"1em",viewBox:"0 0 24 24"},ke=d("path",{fill:"currentColor",d:"M12.001 4.529a5.998 5.998 0 0 1 8.242.228a6 6 0 0 1 .236 8.236l-8.48 8.492l-8.478-8.492a6 6 0 0 1 8.48-8.464z"},null,-1),Ce=[ke];function Me(o,n,r,e,_,u){return s(),l("svg",we,Ce)}const Le=k(ge,[["render",Me]]),Se={name:"RiHeartLine"},Be={width:"1em",height:"1em",viewBox:"0 0 24 24"},Re=d("path",{fill:"currentColor",d:"M12.001 4.529a5.998 5.998 0 0 1 8.242.228a6 6 0 0 1 .236 8.236l-8.48 8.492l-8.478-8.492a6 6 0 0 1 8.48-8.464zm6.826 1.641a3.998 3.998 0 0 0-5.49-.153l-1.335 1.198l-1.336-1.197a3.999 3.999 0 0 0-5.494.154a4 4 0 0 0-.192 5.451L12 18.654l7.02-7.03a4 4 0 0 0-.193-5.454z"},null,-1),Ve=[Re];function De(o,n,r,e,_,u){return s(),l("svg",Be,Ve)}const Ie=k(Se,[["render",De]]);const Oe={key:0,class:"px-2 py-2 group bg-base-lvl-2 font-bold justify-between text-primary w-full flex"},Te={class:"capitalize"},Ae={class:"transition flex space-x-2 items-center text-xl"},je={key:1,class:"flex w-full overflow-visible"},Pe=$("Save"),ze={__name:"MealTypeCell",props:{plannedMeal:{type:[Object,null]},mealType:{type:Object,required:!0}},emits:["submit"],setup(o,{emit:n}){const r=o,e=S({id:"",name:""}),_=(f,m)=>{n("submit",{...e,meal_type_id:r.mealType.id})},u=f=>{n(f,r.plannedMeal)};return(f,m)=>{const h=L("AtButton");return s(),l("div",{class:W(["meal-cell border border-dashed border-primary rounded-md px-5 py-2 w-ful flex overflow-visible mb-4",[o.plannedMeal?"bg-base-lvl-2":"bg-base-lvl-3"]])},[o.plannedMeal?(s(),l("div",Oe,[d("span",Te,y(o.plannedMeal.dateable.name),1),d("div",Ae,[c(h,{class:"hidden transition my-0 text-body-1/80 hover:text-error/80 group-hover:inline-block",onClick:m[0]||(m[0]=v=>u("removed"))},{default:p(()=>[c(xe)]),_:1}),c(h,{class:"group-hover:text-red-500",onClick:m[1]||(m[1]=v=>u("toggle-like"))},{default:p(()=>[o.plannedMeal.is_liked?(s(),g(Le,{key:0})):(s(),g(Ie,{key:1}))]),_:1})])])):(s(),l("div",je,[c(K,{modelValue:e.id,"onUpdate:modelValue":m[2]||(m[2]=v=>e.id=v),label:e.name,"onUpdate:label":m[3]||(m[3]=v=>e.name=v),class:"w-full",tag:"","custom-label":"name","track-id":"id",bordered:!1,placeholder:`Add ${o.mealType.name}`,endpoint:"/api/recipes"},null,8,["modelValue","label","placeholder"]),e.id?(s(),g(Q,{key:0,onClick:_},{default:p(()=>[Pe]),_:1})):H("",!0)]))],2)}}},qe={class:"flex items-center justify-end ml-auto space-x-2"},Fe=$(" Random Meal "),Ge={class:"pb-20 space-x-2"},Ne={key:0,class:"py-5 overflow-hidden border rounded-md bg-base-lvl-1"},Ue={key:1,class:"pt-5 overflow-hidden border divide-y-2 rounded-md text-body divide-base border-base bg-base-lvl-3"},Ee=["onClick"],He={class:"flex items-center mt-2 space-x-2"},it={__name:"Planner",props:{mealPlans:{type:Object,required:!0},meals:{type:Array,required:!0},ingredients:{type:Object,default(){return{}}},mode:{type:String,default:""}},setup(o){const n=o,r=Z().props,e=S({selectedDay:!1,isRandomModalOpen:!1,date:O(new Date),dateSpan:null,mode:"week",selectedMealsOfDay:[],isGroceryList:V(()=>n.mode=="grocery-list"),toggleBtnText:V(()=>e.isGroceryList?"Meal planner":"Grocery List")}),_=()=>{e.isRandomModalOpen=!0},u=t=>T(t,"iiii"),f=(t,i)=>{const a=T(t,"yyyy-MM-dd");return n.mealPlans.find(b=>b.date==a&&b.dateable.meal_type_id==i)},m=t=>t?e.isGroceryList?"":"mode=grocery-list":n.mode?`mode=${n.mode}`:"",h=()=>{x.Inertia.visit(`/meal-planner?${m(!0)}`)},v=t=>{e.selectedDay=t},w=J({date:null,meals:[]}),A=t=>{!t.id&&!t.name||w.transform(()=>({date:O(e.selectedDay),meals:[t]})).post(route("meals.addPlan"),{onSuccess:()=>{w.id="",w.name="",w.reset()}})},j=t=>{t.is_liked=!Boolean(t.is_liked),x.Inertia.put(route("meal-planner.update",t),t,{onSuccess(){x.Inertia.reload({preserveScroll:!0})}})},P=t=>{t.is_liked=!Boolean(t.is_liked),x.Inertia.delete(route("meal-planner.destroy",t),t,{onSuccess(){x.Inertia.reload({preserveScroll:!0})}})};return(t,i)=>(s(),g(X,{title:"Meal Planner"},{header:p(()=>[c(te,null,{actions:p(()=>[d("div",qe,[c(D(N),{class:"w-full h-12 ml-auto border-none bg-base-lvl-1 text-body",modelValue:e.date,"onUpdate:modelValue":i[0]||(i[0]=a=>e.date=a),dateSpan:e.dateSpan,"onUpdate:dateSpan":i[1]||(i[1]=a=>e.dateSpan=a),controlsClass:"bg-transparent text-body hover:bg-base-lvl-1","next-mode":"week"},null,8,["modelValue","dateSpan"]),c(I,{variant:"inverse",class:"w-64 h-10",onClick:i[2]||(i[2]=a=>_())},{default:p(()=>[Fe]),_:1}),c(I,{class:"h-10 w-52",variant:"secondary",onClick:i[3]||(i[3]=a=>h())},{default:p(()=>[$(y(e.toggleBtnText),1)]),_:1})])]),_:1})]),default:p(()=>[c(se,{class:"mx-auto"},{default:p(()=>[d("div",Ge,[e.isGroceryList?(s(),l("div",Ne,[(s(!0),l(C,null,M(o.ingredients,(a,b)=>(s(),l("div",{key:a.id,class:"px-5 cursor-pointer text-primary bg-base-lvl-1"},y(b)+" ("+y(a.quantity)+") "+y(a.unit),1))),128))])):(s(),l("div",Ue,[(s(!0),l(C,null,M(e.dateSpan,a=>(s(),l("div",{key:a,onClick:b=>v(a),class:"px-5 py-4 cursor-pointer bg-base-lvl-3"},[$(y(u(a))+" ",1),d("div",He,[(s(!0),l(C,null,M(D(r).mealTypes,b=>(s(),l("div",{class:"w-full",key:`${b.id}-${a}`},[c(ze,{modelValue:t.recipe,"onUpdate:modelValue":i[4]||(i[4]=z=>t.recipe=z),"planned-meal":f(a,b.id),"meal-type":b,onSubmit:A,onToggleLike:j,onRemoved:P},null,8,["modelValue","planned-meal","meal-type"])]))),128))])],8,Ee))),128))])),c(fe,{show:e.isRandomModalOpen,closeable:!0,onClose:i[5]||(i[5]=a=>e.isRandomModalOpen=!1)},null,8,["show"])])]),_:1})]),_:1}))}};export{it as default};
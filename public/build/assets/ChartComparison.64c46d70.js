import{_ as h}from"./LogerChart.268f3f49.js";import{_ as b}from"./exact-math.node.6f837759.js";import{a as x}from"./AppLayout.cd77f56f.js";import{f as c}from"./index.341cb78b.js";import{f as y}from"./formatMoney.a91b934f.js";import{_ as C}from"./_plugin-vue_export-helper.cdc0426e.js";import{r as k,q as g,s as S,o as l,c as d,d as a,g as w,b as j,e as u,i as f,t as n,f as _,F as $,h as B,a as v,L as I,M as N}from"./app.cefd7668.js";const O=s=>(I("data-v-7c0fd84f"),s=s(),N(),s),V={class:"w-full comparison-card"},D={class:"pb-10 px-5 rounded-lg"},E={class:"card-title text-left p-4 font-bold"},F=O(()=>a("i",{class:"fa fa-arrow-left"},null,-1)),M={key:1,class:"capitalize text-primary"},q={class:"card-text"},L={class:"comparison-header px-10 text-body-1/50 space-x-2 divide-x divide-dashed divide-opacity-20 divide-body-1 bg-base-lvl-2 mb-2"},z=["onClick"],T={class:"period-title"},A={class:"period-value text-xs mt-2"},G={__name:"ChartComparison",props:{title:{type:String},type:{type:String,default:"bar"},data:{type:Object,required:!0}},setup(s){const r=s,t=k(),m=g(()=>{const i=[{name:"Expenses",data:Object.values(r.data).map(e=>e.total),labels:Object.keys(r.data).map(c)}],o=t.value?[{name:c(t.value),data:r.data[t.value].data.map(e=>e.total),labels:r.data[t.value].data.map(e=>e.name)}]:[];return t.value?o:i}),p=S({headers:Object.entries(r.data).map(([i,o])=>({label:c(i),value:o.total,id:i})),options:{colors:["#8a00d4","#80CDFE"]},series:m});return(i,o)=>(l(),d("div",V,[a("div",D,[a("h5",E,[t.value?(l(),w(x,{key:0,onClick:o[0]||(o[0]=e=>t.value=null)},{default:j(()=>[F]),_:1})):u("",!0),f(" "+n(s.title)+" ",1),t.value?(l(),d("span",M,n(_(c)(t.value)),1)):u("",!0)]),a("div",q,[a("div",L,[(l(!0),d($,null,B(p.headers,e=>(l(),d("div",{key:e.id,onClick:H=>t.value=e.id,class:"comparison-header__item justify-center w-full items-center flex-col flex previous-period cursor-pointer hover:text-body/80"},[a("h6",T,n(e.label),1),a("span",A,[v(b),f(" "+n(_(y)(e.value)),1)])],8,z))),128))]),v(h,{style:{height:"300px",background:"white",width:"100%"},label:"name",type:"bar",labels:_(m)[0].labels,options:p.options,series:p.series},null,8,["labels","options","series"])])])]))}},X=C(G,[["__scopeId","data-v-7c0fd84f"]]);export{X as C};
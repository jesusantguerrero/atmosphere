import{r as S,q as w,s as N,o as a,c as l,d as o,t as v,F as b,h as _,K as T,a as h,i as O,f as d,k as C,n as y,e as B,b as p,g as x,B as E,a1 as I,A as R,a6 as V}from"./app.cefd7668.js";import{_ as q}from"./AppLayout.cd77f56f.js";import{u as A,_ as F,a as z}from"./useServerSearch.d123d8c9.js";import{_ as W,a as k}from"./DonutChart.b6408ba4.js";import{f as g}from"./formatMoney.a91b934f.js";import{_ as L}from"./LogerChart.268f3f49.js";import{_ as K}from"./exact-math.node.6f837759.js";import{_ as U}from"./_plugin-vue_export-helper.cdc0426e.js";import{a6 as G}from"./index.de789d8e.js";import{p as H}from"./index.557802c7.js";import{u as D}from"./useCollapse.f5ae3b3d.js";import{f as J}from"./index.341cb78b.js";import"./atmosphere-ui.588c294c.js";import"./Modal.a2277346.js";/* empty css                                              */import"./LogerInput.bec0734e.js";import"./SectionTitle.23b660a7.js";import"./ImportResourceModal.ab0a2563.js";import"./LogerButton.0789417e.js";import"./SectionNav.d2e4d6cd.js";import"./index.4ea4832e.js";const P={class:"w-full comparison-card"},Q={class:"pb-10 px-5 rounded-lg"},X={class:"card-title text-left p-4 font-bold"},Y={class:"card-text"},Z={class:"comparison-header px-10 text-body-1/50 space-x-2 divide-x divide-dashed divide-opacity-20 divide-body-1 bg-base-lvl-2 mb-2"},ee=["onClick"],te={class:"period-title"},ae={class:"period-value"},se={__name:"ChartNetworth",props:{title:{type:String},type:{type:String,default:"bar"},data:{type:Object,required:!0}},setup(t){const r=t,f=e=>G(H(e),"MMMM"),u=S(),n=w(()=>[{name:"Debts",data:Object.values(r.data).map(e=>Math.abs(e.debts)),labels:Object.values(r.data).map(e=>e.month_date)},{name:"Assets",data:Object.values(r.data).map(e=>e.assets),labels:Object.values(r.data).map(e=>e.month_date)}]),i=N({headers:Object.entries(r.data).map(([e,s])=>({label:f(s.month_date),value:[s.debts,s.assets],id:s.month_date})),options:{colors:["#8a00d4","#80CDFE"]},series:n});return(e,s)=>(a(),l("div",P,[o("div",Q,[o("h5",X,v(t.title),1),o("div",Y,[o("div",Z,[(a(!0),l(b,null,_(i.headers,m=>(a(),l("div",{key:m.id,onClick:c=>u.value=m.id,class:"comparison-header__item justify-center w-full items-center flex-col flex previous-period cursor-pointer hover:text-body/80"},[o("h6",te,v(m.label),1),(a(!0),l(b,null,_(m.value,(c,M)=>(a(),l("span",ae,[o("div",{class:"absolute -left-4 top-2 h-2 w-2 rounded-full",style:T({backgroundColor:i.options.colors[M]})},null,4),h(K),O(" "+v(d(g)(c)),1)]))),256))],8,ee))),128))]),h(L,{label:"name",type:"bar",labels:d(n)[0].labels,options:i.options,series:i.series},null,8,["labels","options","series"])])])]))}},le=U(se,[["__scopeId","data-v-27ff585a"]]),$={__name:"Collapse",props:{title:{type:String},titleClass:{type:String},gap:{type:Boolean,default:!0},contentClass:{type:String}},setup(t){const r=S(),{isCollapsed:f,icon:u,toggleCollapse:n}=D(r);return(i,e)=>(a(),l("article",{ref_key:"incomeRef",ref:r},[o("header",{class:y(["cursor-pointer",t.titleClass]),onClick:e[0]||(e[0]=s=>d(n)())},[C(i.$slots,"header",{icon:d(u),isCollapsed:d(f)},()=>[o("i",{class:y([d(u),"mr-2"])},null,2),C(i.$slots,"title",{},()=>[O(v(t.title),1)])])],2),d(f)?B("",!0):(a(),l("section",{key:0,class:y([t.gap&&"pl-4",t.contentClass])},[C(i.$slots,"content")],2))],512))}},j={__name:"TableCell",props:{col:{type:Object,required:!0},value:{type:[String,Number,Date]}},setup(t){return(r,f)=>(a(),l("div",{class:y(t.col.class)},v(t.col.render?t.col.render(t.value):t.value),3))}},re={class:"px-4 py-2 divide-y"},ne={class:"flex mt-4 bg-base px-4 py-2"},oe={class:"mt-2"},ie={__name:"IncomeExpenses",props:{data:{type:Object}},setup(t){const r=t,f=S();D(f);const u=w(()=>[{field:"name",class:"font-bold text-sm"},...r.data.dateUnits.map(n=>({field:n,label:J(n),class:"text-right text-sm",render:i=>g(i||0)})),{field:"avg",label:"AVG",class:"text-right text-sm",render:n=>g(n||0)},{field:"total",label:"Total",class:"text-right text-sm",render:n=>g(n||0)}]);return(n,i)=>(a(),l("section",re,[o("header",ne,[(a(!0),l(b,null,_(d(u),e=>(a(),l("div",{key:e.field,class:y(["w-full font-bold capitalize",e.class])},v(e.label||e.field),3))),128))]),h($,{title:"Income","title-class":"text-success font-bold bg-base-lvl-1 hover:bg-base p-2","content-class":"pb-4",gap:!1},{content:p(()=>[(a(!0),l(b,null,_(t.data.incomeCategories,e=>(a(),l("div",{key:e.id,class:"flex space-x-2 group hover:bg-primary/5"},[(a(!0),l(b,null,_(d(u),s=>(a(),x(j,{key:s.field,class:y(["w-full px-2 py-2 cursor-pointer hover:font-bold hover:text-primary transition",{"group-hover:text-primary":s.field=="name"}]),title:s.label,col:s,value:t.data.incomes[e.name][s.field]},null,8,["class","title","col","value"]))),128))]))),128))]),_:1}),h($,{title:"Expenses","title-class":"text-error font-bold bg-base-lvl-1 hover:bg-base p-2","content-class":"pb-4",gap:!1},{content:p(()=>[o("section",oe,[(a(!0),l(b,null,_(t.data.expenseCategories,(e,s)=>(a(),x($,{title:s,"title-class":"font-bold bg-base-lvl-2 py-1 rounded-md px-2",key:s,class:"mt-2",gap:!1},{content:p(()=>[(a(!0),l(b,null,_(e,m=>(a(),l("div",{key:m.id,class:"flex space-x-2 group hover:bg-primary/5"},[(a(!0),l(b,null,_(d(u),c=>(a(),x(j,{key:c.field,class:y(["w-full px-2 py-2 cursor-pointer hover:font-bold hover:text-primary transition",{"group-hover:text-primary":c.field=="name"}]),title:c.label,col:c,value:t.data.expenses[`${s}:${m.name}`][c.field]},null,8,["class","title","col","value"]))),128))]))),128))]),_:2},1032,["title"]))),128))])]),_:1})]))}},ce={class:"flex flex-wrap mt-5 md:flex-nowrap md:space-x-8"},de={class:"w-full md:w-full"},ue={class:"divide-y-2"},Be={__name:"Trends",props:{user:{type:Object,required:!0},data:{type:Array,default(){return[]}},metaData:{type:Object},serverSearchOptions:{type:Object,default:()=>({})}},setup(t){const r=t,{serverSearchOptions:f}=E(r);A(f);const u=s=>{const m=r.data[s];r.metaData.parent_name||V.Inertia.visit(`/trends/categories?filter[parent_id]=${m.id}`)},n=[{name:"Category Group Trends",link:"/trends"},{name:"Category Trends",link:"/trends/categories"},{name:"Net Worth",link:"/trends/net-worth"},{name:"Income v Expenses",link:"/trends/income-expenses"}],i={groups:k,categories:k,netWorth:le,incomeExpenses:ie},e=w(()=>i[r.metaData.name]||k);return(s,m)=>(a(),x(q,{title:t.metaData.title},{header:p(()=>[h(F,null,{actions:p(()=>[]),_:1})]),default:p(()=>[h(z,{title:"Finance",accounts:s.accounts,ref:"financeTemplateRef"},{panel:p(()=>[o("div",ue,[(a(),l(b,null,_(n,c=>h(d(I),{href:c.link,key:c.name,class:"block py-4 px-2 hover:bg-base-lvl-3 cursor-pointer"},{default:p(()=>[O(v(c.name),1)]),_:2},1032,["href"])),64))])]),default:p(()=>[o("section",null,[o("div",ce,[o("div",de,[h(W,{"section-title":t.metaData.title},{default:p(()=>[(a(),x(R(d(e)),{style:{background:"white",width:"100%"},series:t.data,data:t.data,onClicked:u,label:"name",value:"total",legend:!1},null,40,["series","data"]))]),_:1},8,["section-title"])])])])]),_:1},8,["accounts"])]),_:1},8,["title"]))}};export{Be as default};
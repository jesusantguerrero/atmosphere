/* empty css                                                 */import{s as n,l as i,o as s,c as a,d as u,F as h,h as k,g as l,e as c,n as p,t as r}from"./app.cefd7668.js";const m={class:"tab-header flex mock-group"},g=["onClick"],v={__name:"mockTask",setup(_){const e=n({tabs:{todo:"text-green-500",schedule:"text-blue-500"},tabSelected:"todo",todo:[{title:"Task 1",key:!0,checklist:[],tags:[]},{title:"Task 2",checklist:[],uid:"task2",tags:[]}],schedule:[{title:"Task 3",checklist:[],uid:"task3",tags:[]}]});return(y,x)=>{const o=i("task-group");return s(),a("div",null,[u("div",m,[(s(!0),a(h,null,k(e.tabs,(d,t)=>(s(),a("button",{class:p(["px-2 py-1 hover:bg-gray-200 font-bold focus:outline-none text-gray-500 capitalize rounded-md ml-2",{[`${d} bg-gray-100`]:e.tabSelected==t}]),key:t,onClick:b=>e.tabSelected=t},r(t)+" ("+r(e[t].length)+") ",11,g))),128))]),e.tabSelected=="todo"?(s(),l(o,{key:0,"show-title":!1,title:"Todo",class:"py-3",tasks:e.todo,"show-select":!0,"show-controls":!0,"is-item-as-handler":!0,"search-text":"","drag-class":"shadow-md px-2 py-2","task-class":"shadow-md",type:"todo",tags:[],placeholder:"Click a task select"},null,8,["tasks"])):c("",!0),e.tabSelected=="schedule"?(s(),l(o,{key:1,"show-title":!1,title:"Schedule",tasks:e.schedule,tags:[],active:!1,"show-controls":!0,"search-text":"",type:"schedule",class:"py-3","drag-class":"shadow-md","task-class":"shadow-md",placeholder:"Move task to todo to select","is-item-as-handler":!0},null,8,["tasks"])):c("",!0)])}}};export{v as default};
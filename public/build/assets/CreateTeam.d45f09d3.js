import u from"./AcceptInvitation.50fd6dd6.js";import{Z as l}from"./atmosphere-ui.87ba2c0a.js";import{s as _,u as f,o as a,g as n,b as s,d as e,f as m,e as y,i as x}from"./app.d8e74fa3.js";import{_ as b}from"./AppLayout.9d561a06.js";import v from"./TeamForm.b6213a84.js";import{as as g}from"./naiveui.0ad5ca37.js";import"./_plugin-vue_export-helper.cdc0426e.js";import"./Modal.36144344.js";/* empty css                                              */import"./LogerInput.c4e88f46.js";const h={class:"h-auto py-12 mx-auto max-w-7xl sm:px-6 lg:px-8"},w={class:"flex items-center justify-between py-2 mb-10 border-4 border-white rounded-md bg-gray-50"},T=e("div",{class:"px-5 font-bold text-gray-600"}," Space Setup ",-1),C={class:"flex overflow-hidden font-bold text-gray-500 rounded-lg max-w-min"},k=x(" Create Space"),M={class:"w-full text-right"},A={__name:"CreateTeam",props:{invitations:{type:Array,default:()=>[]}},setup(c){const r=_({currentMode:"createTeam"}),i=f({name:"",timezone:"UTC",primary_currency_code:"USD",currency_symbol_option:"before",date_format:""}),d=()=>{i.transform(t=>(t.primary_currency_code=t.primary_currency_code.code||t.primary_currency_code,g(t))).post("/onboarding")};return(t,o)=>(a(),n(b,{"is-onboarding":!0},{default:s(()=>[e("div",h,[e("div",w,[T,e("div",C,[r.currentMode=="createTeam"?(a(),n(m(l),{key:0,disabled:!m(i).name,onClick:d,class:"w-48 bg-primary text-white"},{default:s(()=>[k]),_:1},8,["disabled"])):y("",!0)])]),r.currentMode=="createTeam"?(a(),n(v,{key:0,class:"w-full px-5 py-4 space-y-5 bg-white rounded-md","form-data":m(i)},{append:s(()=>[e("div",M,[e("button",{onClick:o[0]||(o[0]=p=>r.currentMode="invite"),class:"font-bold text-primary/80 underline hover:text-primary"}," I have got an invitation ")])]),_:1},8,["form-data"])):(a(),n(u,{key:1,onChange:o[1]||(o[1]=p=>r.currentMode="createTeam"),invitations:c.invitations},null,8,["invitations"]))])]),_:1}))}};export{A as default};
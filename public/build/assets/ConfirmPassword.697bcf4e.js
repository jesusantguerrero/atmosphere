import{J as _}from"./AuthenticationCard.67266227.js";import{_ as f}from"./AuthenticationCardLogo.f4fea4eb.js";import{J as b}from"./Button.cdbb2fb8.js";import{J as h}from"./Input.3189304c.js";import{_ as w}from"./Label.d85f9a67.js";import{J as j}from"./ValidationErrors.68382b3c.js";import{v as o,o as v,k as J,w as n,d as t,b as r,y as x,x as g,f as C}from"./app.c4e32cc5.js";import{_ as V}from"./plugin-vue_export-helper.21dcd24c.js";const y={components:{JetAuthenticationCard:_,JetAuthenticationCardLogo:f,JetButton:b,JetInput:h,JetLabel:w,JetValidationErrors:j},data(){return{form:this.$inertia.form({password:""})}},methods:{submit(){this.form.post(this.route("password.confirm"),{onFinish:()=>this.form.reset()})}}},k=r("div",{class:"mb-4 text-sm text-gray-600"}," This is a secure area of the application. Please confirm your password before continuing. ",-1),B={class:"flex justify-end mt-4"},N=C(" Confirm ");function P(A,e,L,T,s,i){const m=o("jet-authentication-card-logo"),c=o("jet-validation-errors"),l=o("jet-label"),d=o("jet-input"),p=o("jet-button"),u=o("jet-authentication-card");return v(),J(u,null,{logo:n(()=>[t(m)]),default:n(()=>[k,t(c,{class:"mb-4"}),r("form",{onSubmit:e[1]||(e[1]=g((...a)=>i.submit&&i.submit(...a),["prevent"]))},[r("div",null,[t(l,{for:"password",value:"Password"}),t(d,{id:"password",type:"password",class:"mt-1 block w-full",modelValue:s.form.password,"onUpdate:modelValue":e[0]||(e[0]=a=>s.form.password=a),required:"",autocomplete:"current-password",autofocus:""},null,8,["modelValue"])]),r("div",B,[t(p,{class:x(["ml-4",{"opacity-25":s.form.processing}]),disabled:s.form.processing},{default:n(()=>[N]),_:1},8,["class","disabled"])])],32)]),_:1})}var $=V(y,[["render",P]]);export{$ as default};
import{J as f}from"./AuthenticationCard.c1e1bbaf.js";import{_}from"./AuthenticationCardLogo.182f9980.js";import{J as w}from"./Button.bbd6a321.js";import{J as b}from"./Input.5bef6e23.js";import{_ as h}from"./Label.63d2f11c.js";import{J as j}from"./ValidationErrors.816ddcd9.js";import{l as r,o as V,g as v,b as l,a as o,d as a,n as J,m as g,i as k}from"./app.cefd7668.js";import{_ as x}from"./_plugin-vue_export-helper.cdc0426e.js";const C={components:{JetAuthenticationCard:f,JetAuthenticationCardLogo:_,JetButton:w,JetInput:b,JetLabel:h,JetValidationErrors:j},props:{email:String,token:String},data(){return{form:this.$inertia.form({token:this.token,email:this.email,password:"",password_confirmation:""})}},methods:{submit(){this.form.post(this.route("password.update"),{onFinish:()=>this.form.reset("password","password_confirmation")})}}},y={class:"mt-4"},B={class:"mt-4"},P={class:"flex items-center justify-end mt-4"},q=k(" Reset Password ");function N(S,t,U,A,e,m){const d=r("jet-authentication-card-logo"),p=r("jet-validation-errors"),n=r("jet-label"),i=r("jet-input"),c=r("jet-button"),u=r("jet-authentication-card");return V(),v(u,null,{logo:l(()=>[o(d)]),default:l(()=>[o(p,{class:"mb-4"}),a("form",{onSubmit:t[3]||(t[3]=g((...s)=>m.submit&&m.submit(...s),["prevent"]))},[a("div",null,[o(n,{for:"email",value:"Email"}),o(i,{id:"email",type:"email",class:"mt-1 block w-full",modelValue:e.form.email,"onUpdate:modelValue":t[0]||(t[0]=s=>e.form.email=s),required:"",autofocus:""},null,8,["modelValue"])]),a("div",y,[o(n,{for:"password",value:"Password"}),o(i,{id:"password",type:"password",class:"mt-1 block w-full",modelValue:e.form.password,"onUpdate:modelValue":t[1]||(t[1]=s=>e.form.password=s),required:"",autocomplete:"new-password"},null,8,["modelValue"])]),a("div",B,[o(n,{for:"password_confirmation",value:"Confirm Password"}),o(i,{id:"password_confirmation",type:"password",class:"mt-1 block w-full",modelValue:e.form.password_confirmation,"onUpdate:modelValue":t[2]||(t[2]=s=>e.form.password_confirmation=s),required:"",autocomplete:"new-password"},null,8,["modelValue"])]),a("div",P,[o(c,{class:J({"opacity-25":e.form.processing}),disabled:e.form.processing},{default:l(()=>[q]),_:1},8,["class","disabled"])])],32)]),_:1})}const D=x(C,[["render",N]]);export{D as default};
import{J as k}from"./ActionMessage.4b08d72d.js";import{_ as x}from"./ActionSection.4ee9e188.js";import{J as b}from"./Button.cdbb2fb8.js";import{J as S}from"./DialogModal.66d3fddc.js";import{J as B}from"./Input.3189304c.js";import{J as C}from"./InputError.4e908049.js";import{J}from"./SecondaryButton.9c7b98b2.js";import{v as n,o as s,k as j,w as t,a as i,F as L,e as M,i as O,b as o,d as c,P as V,y as A,f as a,t as d}from"./app.c4e32cc5.js";import{_ as F}from"./plugin-vue_export-helper.21dcd24c.js";import"./SectionTitle.f0e58d40.js";import"./Modal.50d5072f.js";const I={props:["sessions"],components:{JetActionMessage:k,JetActionSection:x,JetButton:b,JetDialogModal:S,JetInput:B,JetInputError:C,JetSecondaryButton:J},data(){return{confirmingLogout:!1,form:this.$inertia.form({password:""})}},methods:{confirmLogout(){this.confirmingLogout=!0,setTimeout(()=>this.$refs.password.focus(),250)},logoutOtherBrowserSessions(){this.form.delete(route("other-browser-sessions.destroy"),{preserveScroll:!0,onSuccess:()=>this.closeModal(),onError:()=>this.$refs.password.focus(),onFinish:()=>this.form.reset()})},closeModal(){this.confirmingLogout=!1,this.form.reset()}}},N=o("div",{class:"max-w-xl text-sm"}," If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password. ",-1),z={key:0,class:"mt-5 space-y-6"},D={key:0,fill:"none","stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2",viewBox:"0 0 24 24",stroke:"currentColor",class:"w-8 h-8 text-gray-500"},E=o("path",{d:"M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"},null,-1),K=[E],P={key:1,xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24","stroke-width":"2",stroke:"currentColor",fill:"none","stroke-linecap":"round","stroke-linejoin":"round",class:"w-8 h-8 text-gray-500"},T=o("path",{d:"M0 0h24v24H0z",stroke:"none"},null,-1),H=o("rect",{x:"7",y:"4",width:"10",height:"16",rx:"1"},null,-1),U=o("path",{d:"M11 5h2M12 17v.01"},null,-1),q=[T,H,U],G={class:"ml-3"},Q={class:"text-sm text-gray-600"},R={class:"text-xs text-gray-500"},W={key:0,class:"text-green-500 font-semibold"},X={key:1},Y={class:"flex items-center mt-5"},Z=a(" Log Out Other Browser Sessions "),$=a(" Done. "),oo=a(" Log Out Other Browser Sessions "),eo=a(" Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices. "),so={class:"mt-4"},to=a(" Cancel "),ro=a(" Log Out Other Browser Sessions ");function no(io,_,u,co,r,l){const m=n("jet-button"),h=n("jet-action-message"),f=n("jet-input"),p=n("jet-input-error"),g=n("jet-secondary-button"),w=n("jet-dialog-modal"),y=n("JetActionSection");return s(),j(y,{title:"Browser Sessions",description:"Manage and log out your active sessions on other browsers and devices."},{content:t(()=>[N,u.sessions.length>0?(s(),i("div",z,[(s(!0),i(L,null,M(u.sessions,(e,v)=>(s(),i("div",{class:"flex items-center",key:v},[o("div",null,[e.agent.is_desktop?(s(),i("svg",D,K)):(s(),i("svg",P,q))]),o("div",G,[o("div",Q,d(e.agent.platform)+" - "+d(e.agent.browser),1),o("div",null,[o("div",R,[a(d(e.ip_address)+", ",1),e.is_current_device?(s(),i("span",W,"This device")):(s(),i("span",X,"Last active "+d(e.last_active),1))])])])]))),128))])):O("",!0),o("div",Y,[c(m,{onClick:l.confirmLogout},{default:t(()=>[Z]),_:1},8,["onClick"]),c(h,{on:r.form.recentlySuccessful,class:"ml-3"},{default:t(()=>[$]),_:1},8,["on"])]),c(w,{show:r.confirmingLogout,onClose:l.closeModal},{title:t(()=>[oo]),content:t(()=>[eo,o("div",so,[c(f,{type:"password",class:"mt-1 block w-3/4",placeholder:"Password",ref:"password",modelValue:r.form.password,"onUpdate:modelValue":_[0]||(_[0]=e=>r.form.password=e),onKeyup:V(l.logoutOtherBrowserSessions,["enter"])},null,8,["modelValue","onKeyup"]),c(p,{message:r.form.errors.password,class:"mt-2"},null,8,["message"])])]),footer:t(()=>[c(g,{onClick:l.closeModal},{default:t(()=>[to]),_:1},8,["onClick"]),c(m,{class:A(["ml-2",{"opacity-25":r.form.processing}]),onClick:l.logoutOtherBrowserSessions,disabled:r.form.processing},{default:t(()=>[ro]),_:1},8,["onClick","class","disabled"])]),_:1},8,["show","onClose"])]),_:1})}var vo=F(I,[["render",no]]);export{vo as default};
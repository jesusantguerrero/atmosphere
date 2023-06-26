import{L as kn}from"./atmosphere-ui-5b718e83.js";import{D as ne,L as c,f as L,p as S,B as Ct,U as ie,E as Te,J as Ht,M as yt,N as ht,F as ze,V as So,S as Vt,W as wn,X as Rn,Y as Sn,o as Q,e as re,b as se,a as be,g as Ut,q as xt,x as zn,w as ye,h as Ue,t as Ie,I as Pn,i as kt,u as me,n as et,c as ct,k as zo,Z as Fn,A as _n,O as Bt,C as Mn}from"./app-fc313f38.js";import{i as $n,b as Jt,F as Qt,B as eo,c as to,d as oo,e as Tn,f as no,_ as Bn}from"./AppLayout.vue_vue_type_script_setup_true_lang-87835da0.js";import{u as On,_ as An,F as Ln}from"./useServerSearch-8502c7d2.js";import{f as ut,p as Dn}from"./formatMoney-da670e0a.js";import{_ as En,C as Un}from"./ChartComparison-8bcea9ac.js";import{i as it,j as It,k as Be,l as wt,m as Ze,n as Rt,o as D,p as U,q as R,s as oe,v as O,w as dt,x as Po,y as Fo,z as Ge,A as Ce,B as St,C as fe,D as ft,E as _o,F as Mo,G as Nt,H as mt,I as Wt,J as In,K as $o,L as Nn,M as jn,O as jt,P as Kn,Q as ro,N as zt,R as Kt,S as Hn,T as at,U as To,V as qt,e as Vn,W as qe,X as Wn,Y as ve,Z as qn,_ as Gn,$ as Xn,a0 as Zn,a1 as Ot,a2 as Ne,a3 as Bo,a4 as vt,a5 as Yn,a6 as pt,a7 as ao,a8 as Oo,a9 as Jn,aa as Qn,ab as io,ac as er,ad as tr,ae as or,af as lo,ag as nr,ah as rr,ai as ar,f as ir}from"./isMobile-3d69cdae.js";import{_ as lr}from"./NumberHider-888bcf03.js";import{_ as sr}from"./_plugin-vue_export-helper-c27b6911.js";import{u as Ao}from"./useCollapse-c520c1e7.js";import{f as dr}from"./index-3a6f8c36.js";import{_ as cr,a as ur}from"./WidgetTitleCard-36ba890b.js";import{e as fr}from"./exact-math.node-672740b8.js";import{c as hr,d as pr,N as br,C as mr}from"./Dropdown-1335b583.js";import{_ as so}from"./ExpenseIncome.vue_vue_type_script_setup_true_lang-84653f02.js";import"./index-975c57c9.js";import"./AppIcon-36bb04e5.js";/* empty css                                                */import"./Modal-ca404241.js";/* empty css                                              */import"./LogerInput-2f23c230.js";import"./LogerButton-370ed8f9.js";import"./vue-draggable-next.esm-bundler-25f0042a.js";import"./constants-cb26b0c1.js";import"./WidgetCard-97abea05.js";import"./SectionTitle-df81ac17.js";import"./SectionNav-7e8b141c.js";import"./chart-eb93a947.js";import"./index-b9ffb747.js";import"./auto-4dc61a7a.js";function vr(e,t="default",o=[]){const r=e.$slots[t];return r===void 0?o:r()}function Lo(e,t=[],o){const n={};return Object.getOwnPropertyNames(e).forEach(l=>{t.includes(l)||(n[l]=e[l])}),Object.assign(n,o)}function gr(e){return Object.keys(e)}function co(e){switch(e){case"tiny":return"mini";case"small":return"tiny";case"medium":return"small";case"large":return"medium";case"huge":return"large"}throw Error(`${e} has no smaller size.`)}const xr=ne({name:"ArrowDown",render(){return c("svg",{viewBox:"0 0 28 28",version:"1.1",xmlns:"http://www.w3.org/2000/svg"},c("g",{stroke:"none","stroke-width":"1","fill-rule":"evenodd"},c("g",{"fill-rule":"nonzero"},c("path",{d:"M23.7916,15.2664 C24.0788,14.9679 24.0696,14.4931 23.7711,14.206 C23.4726,13.9188 22.9978,13.928 22.7106,14.2265 L14.7511,22.5007 L14.7511,3.74792 C14.7511,3.33371 14.4153,2.99792 14.0011,2.99792 C13.5869,2.99792 13.2511,3.33371 13.2511,3.74793 L13.2511,22.4998 L5.29259,14.2265 C5.00543,13.928 4.53064,13.9188 4.23213,14.206 C3.93361,14.4931 3.9244,14.9679 4.21157,15.2664 L13.2809,24.6944 C13.6743,25.1034 14.3289,25.1034 14.7223,24.6944 L23.7916,15.2664 Z"}))))}}),yr=ne({name:"Filter",render(){return c("svg",{viewBox:"0 0 28 28",version:"1.1",xmlns:"http://www.w3.org/2000/svg"},c("g",{stroke:"none","stroke-width":"1","fill-rule":"evenodd"},c("g",{"fill-rule":"nonzero"},c("path",{d:"M17,19 C17.5522847,19 18,19.4477153 18,20 C18,20.5522847 17.5522847,21 17,21 L11,21 C10.4477153,21 10,20.5522847 10,20 C10,19.4477153 10.4477153,19 11,19 L17,19 Z M21,13 C21.5522847,13 22,13.4477153 22,14 C22,14.5522847 21.5522847,15 21,15 L7,15 C6.44771525,15 6,14.5522847 6,14 C6,13.4477153 6.44771525,13 7,13 L21,13 Z M24,7 C24.5522847,7 25,7.44771525 25,8 C25,8.55228475 24.5522847,9 24,9 L4,9 C3.44771525,9 3,8.55228475 3,8 C3,7.44771525 3.44771525,7 4,7 L24,7 Z"}))))}}),uo=ne({name:"More",render(){return c("svg",{viewBox:"0 0 16 16",version:"1.1",xmlns:"http://www.w3.org/2000/svg"},c("g",{stroke:"none","stroke-width":"1",fill:"none","fill-rule":"evenodd"},c("g",{fill:"currentColor","fill-rule":"nonzero"},c("path",{d:"M4,7 C4.55228,7 5,7.44772 5,8 C5,8.55229 4.55228,9 4,9 C3.44772,9 3,8.55229 3,8 C3,7.44772 3.44772,7 4,7 Z M8,7 C8.55229,7 9,7.44772 9,8 C9,8.55229 8.55229,9 8,9 C7.44772,9 7,8.55229 7,8 C7,7.44772 7.44772,7 8,7 Z M12,7 C12.5523,7 13,7.44772 13,8 C13,8.55229 12.5523,9 12,9 C11.4477,9 11,8.55229 11,8 C11,7.44772 11.4477,7 12,7 Z"}))))}}),Cr={sizeSmall:"14px",sizeMedium:"16px",sizeLarge:"18px",labelPadding:"0 8px",labelFontWeight:"400"},kr=e=>{const{baseColor:t,inputColorDisabled:o,cardColor:n,modalColor:r,popoverColor:l,textColorDisabled:d,borderColor:a,primaryColor:i,textColor2:s,fontSizeSmall:v,fontSizeMedium:f,fontSizeLarge:p,borderRadiusSmall:u,lineHeight:h}=e;return Object.assign(Object.assign({},Cr),{labelLineHeight:h,fontSizeSmall:v,fontSizeMedium:f,fontSizeLarge:p,borderRadius:u,color:t,colorChecked:i,colorDisabled:o,colorDisabledChecked:o,colorTableHeader:n,colorTableHeaderModal:r,colorTableHeaderPopover:l,checkMarkColor:t,checkMarkColorDisabled:d,checkMarkColorDisabledChecked:d,border:`1px solid ${a}`,borderDisabled:`1px solid ${a}`,borderDisabledChecked:`1px solid ${a}`,borderChecked:`1px solid ${i}`,borderFocus:`1px solid ${i}`,boxShadowFocus:`0 0 0 2px ${It(i,{alpha:.3})}`,textColor:s,textColorDisabled:d})},wr={name:"Checkbox",common:it,self:kr},Do=wr,Rr=c("svg",{viewBox:"0 0 64 64",class:"check-icon"},c("path",{d:"M50.42,16.76L22.34,39.45l-8.1-11.46c-1.12-1.58-3.3-1.96-4.88-0.84c-1.58,1.12-1.95,3.3-0.84,4.88l10.26,14.51  c0.56,0.79,1.42,1.31,2.38,1.45c0.16,0.02,0.32,0.03,0.48,0.03c0.8,0,1.57-0.27,2.2-0.78l30.99-25.03c1.5-1.21,1.74-3.42,0.52-4.92  C54.13,15.78,51.93,15.55,50.42,16.76z"})),Sr=c("svg",{viewBox:"0 0 100 100",class:"line-icon"},c("path",{d:"M80.2,55.5H21.4c-2.8,0-5.1-2.5-5.1-5.5l0,0c0-3,2.3-5.5,5.1-5.5h58.7c2.8,0,5.1,2.5,5.1,5.5l0,0C85.2,53.1,82.9,55.5,80.2,55.5z"})),Eo=Rt("n-checkbox-group"),zr={min:Number,max:Number,size:String,value:Array,defaultValue:{type:Array,default:null},disabled:{type:Boolean,default:void 0},"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array],onChange:[Function,Array]},Pr=ne({name:"CheckboxGroup",props:zr,setup(e){const{mergedClsPrefixRef:t}=Be(e),o=wt(e),{mergedSizeRef:n,mergedDisabledRef:r}=o,l=L(e.defaultValue),d=S(()=>e.value),a=Ze(d,l),i=S(()=>{var f;return((f=a.value)===null||f===void 0?void 0:f.length)||0}),s=S(()=>Array.isArray(a.value)?new Set(a.value):new Set);function v(f,p){const{nTriggerFormInput:u,nTriggerFormChange:h}=o,{onChange:m,"onUpdate:value":b,onUpdateValue:x}=e;if(Array.isArray(a.value)){const g=Array.from(a.value),P=g.findIndex(j=>j===p);f?~P||(g.push(p),x&&D(x,g,{actionType:"check",value:p}),b&&D(b,g,{actionType:"check",value:p}),u(),h(),l.value=g,m&&D(m,g)):~P&&(g.splice(P,1),x&&D(x,g,{actionType:"uncheck",value:p}),b&&D(b,g,{actionType:"uncheck",value:p}),m&&D(m,g),l.value=g,u(),h())}else f?(x&&D(x,[p],{actionType:"check",value:p}),b&&D(b,[p],{actionType:"check",value:p}),m&&D(m,[p]),l.value=[p],u(),h()):(x&&D(x,[],{actionType:"uncheck",value:p}),b&&D(b,[],{actionType:"uncheck",value:p}),m&&D(m,[]),l.value=[],u(),h())}return Ct(Eo,{checkedCountRef:i,maxRef:ie(e,"max"),minRef:ie(e,"min"),valueSetRef:s,disabledRef:r,mergedSizeRef:n,toggleCheckbox:v}),{mergedClsPrefix:t}},render(){return c("div",{class:`${this.mergedClsPrefix}-checkbox-group`,role:"group"},this.$slots)}}),Fr=U([R("checkbox",`
 line-height: var(--n-label-line-height);
 font-size: var(--n-font-size);
 outline: none;
 cursor: pointer;
 display: inline-flex;
 flex-wrap: nowrap;
 align-items: flex-start;
 word-break: break-word;
 --n-merged-color-table: var(--n-color-table);
 `,[U("&:hover",[R("checkbox-box",[oe("border",{border:"var(--n-border-checked)"})])]),U("&:focus:not(:active)",[R("checkbox-box",[oe("border",`
 border: var(--n-border-focus);
 box-shadow: var(--n-box-shadow-focus);
 `)])]),O("inside-table",[R("checkbox-box",`
 background-color: var(--n-merged-color-table);
 `)]),O("checked",[R("checkbox-box",`
 background-color: var(--n-color-checked);
 `,[R("checkbox-icon",[U(".check-icon",`
 opacity: 1;
 transform: scale(1);
 `)])])]),O("indeterminate",[R("checkbox-box",[R("checkbox-icon",[U(".check-icon",`
 opacity: 0;
 transform: scale(.5);
 `),U(".line-icon",`
 opacity: 1;
 transform: scale(1);
 `)])])]),O("checked, indeterminate",[U("&:focus:not(:active)",[R("checkbox-box",[oe("border",`
 border: var(--n-border-checked);
 box-shadow: var(--n-box-shadow-focus);
 `)])]),R("checkbox-box",`
 background-color: var(--n-color-checked);
 border-left: 0;
 border-top: 0;
 `,[oe("border",{border:"var(--n-border-checked)"})])]),O("disabled",{cursor:"not-allowed"},[O("checked",[R("checkbox-box",`
 background-color: var(--n-color-disabled-checked);
 `,[oe("border",{border:"var(--n-border-disabled-checked)"}),R("checkbox-icon",[U(".check-icon, .line-icon",{fill:"var(--n-check-mark-color-disabled-checked)"})])])]),R("checkbox-box",`
 background-color: var(--n-color-disabled);
 `,[oe("border",{border:"var(--n-border-disabled)"}),R("checkbox-icon",[U(".check-icon, .line-icon",{fill:"var(--n-check-mark-color-disabled)"})])]),oe("label",{color:"var(--n-text-color-disabled)"})]),R("checkbox-box-wrapper",`
 position: relative;
 width: var(--n-size);
 flex-shrink: 0;
 flex-grow: 0;
 user-select: none;
 -webkit-user-select: none;
 `),R("checkbox-box",`
 position: absolute;
 left: 0;
 top: 50%;
 transform: translateY(-50%);
 height: var(--n-size);
 width: var(--n-size);
 display: inline-block;
 box-sizing: border-box;
 border-radius: var(--n-border-radius);
 background-color: var(--n-color);
 transition: background-color 0.3s var(--n-bezier);
 `,[oe("border",`
 transition:
 border-color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 border-radius: inherit;
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 border: var(--n-border);
 `),R("checkbox-icon",`
 display: flex;
 align-items: center;
 justify-content: center;
 position: absolute;
 left: 1px;
 right: 1px;
 top: 1px;
 bottom: 1px;
 `,[U(".check-icon, .line-icon",`
 width: 100%;
 fill: var(--n-check-mark-color);
 opacity: 0;
 transform: scale(0.5);
 transform-origin: center;
 transition:
 fill 0.3s var(--n-bezier),
 transform 0.3s var(--n-bezier),
 opacity 0.3s var(--n-bezier),
 border-color 0.3s var(--n-bezier);
 `),dt({left:"1px",top:"1px"})])]),oe("label",`
 color: var(--n-text-color);
 transition: color .3s var(--n-bezier);
 user-select: none;
 -webkit-user-select: none;
 padding: var(--n-label-padding);
 font-weight: var(--n-label-font-weight);
 `,[U("&:empty",{display:"none"})])]),Po(R("checkbox",`
 --n-merged-color-table: var(--n-color-table-modal);
 `)),Fo(R("checkbox",`
 --n-merged-color-table: var(--n-color-table-popover);
 `))]),_r=Object.assign(Object.assign({},Ce.props),{size:String,checked:{type:[Boolean,String,Number],default:void 0},defaultChecked:{type:[Boolean,String,Number],default:!1},value:[String,Number],disabled:{type:Boolean,default:void 0},indeterminate:Boolean,label:String,focusable:{type:Boolean,default:!0},checkedValue:{type:[Boolean,String,Number],default:!0},uncheckedValue:{type:[Boolean,String,Number],default:!1},"onUpdate:checked":[Function,Array],onUpdateChecked:[Function,Array],privateInsideTable:Boolean,onChange:[Function,Array]}),Gt=ne({name:"Checkbox",props:_r,setup(e){const t=L(null),{mergedClsPrefixRef:o,inlineThemeDisabled:n,mergedRtlRef:r}=Be(e),l=wt(e,{mergedSize(w){const{size:_}=e;if(_!==void 0)return _;if(i){const{value:I}=i.mergedSizeRef;if(I!==void 0)return I}if(w){const{mergedSize:I}=w;if(I!==void 0)return I.value}return"medium"},mergedDisabled(w){const{disabled:_}=e;if(_!==void 0)return _;if(i){if(i.disabledRef.value)return!0;const{maxRef:{value:I},checkedCountRef:z}=i;if(I!==void 0&&z.value>=I&&!p.value)return!0;const{minRef:{value:C}}=i;if(C!==void 0&&z.value<=C&&p.value)return!0}return w?w.disabled.value:!1}}),{mergedDisabledRef:d,mergedSizeRef:a}=l,i=Te(Eo,null),s=L(e.defaultChecked),v=ie(e,"checked"),f=Ze(v,s),p=Ge(()=>{if(i){const w=i.valueSetRef.value;return w&&e.value!==void 0?w.has(e.value):!1}else return f.value===e.checkedValue}),u=Ce("Checkbox","-checkbox",Fr,Do,e,o);function h(w){if(i&&e.value!==void 0)i.toggleCheckbox(!p.value,e.value);else{const{onChange:_,"onUpdate:checked":I,onUpdateChecked:z}=e,{nTriggerFormInput:C,nTriggerFormChange:K}=l,Z=p.value?e.uncheckedValue:e.checkedValue;I&&D(I,Z,w),z&&D(z,Z,w),_&&D(_,Z,w),C(),K(),s.value=Z}}function m(w){d.value||h(w)}function b(w){if(!d.value)switch(w.key){case" ":case"Enter":h(w)}}function x(w){switch(w.key){case" ":w.preventDefault()}}const g={focus:()=>{var w;(w=t.value)===null||w===void 0||w.focus()},blur:()=>{var w;(w=t.value)===null||w===void 0||w.blur()}},P=St("Checkbox",r,o),j=S(()=>{const{value:w}=a,{common:{cubicBezierEaseInOut:_},self:{borderRadius:I,color:z,colorChecked:C,colorDisabled:K,colorTableHeader:Z,colorTableHeaderModal:Y,colorTableHeaderPopover:X,checkMarkColor:H,checkMarkColorDisabled:V,border:W,borderFocus:te,borderDisabled:ue,borderChecked:y,boxShadowFocus:M,textColor:A,textColorDisabled:T,checkMarkColorDisabledChecked:J,colorDisabledChecked:ee,borderDisabledChecked:he,labelPadding:pe,labelLineHeight:le,labelFontWeight:de,[fe("fontSize",w)]:k,[fe("size",w)]:E}}=u.value;return{"--n-label-line-height":le,"--n-label-font-weight":de,"--n-size":E,"--n-bezier":_,"--n-border-radius":I,"--n-border":W,"--n-border-checked":y,"--n-border-focus":te,"--n-border-disabled":ue,"--n-border-disabled-checked":he,"--n-box-shadow-focus":M,"--n-color":z,"--n-color-checked":C,"--n-color-table":Z,"--n-color-table-modal":Y,"--n-color-table-popover":X,"--n-color-disabled":K,"--n-color-disabled-checked":ee,"--n-text-color":A,"--n-text-color-disabled":T,"--n-check-mark-color":H,"--n-check-mark-color-disabled":V,"--n-check-mark-color-disabled-checked":J,"--n-font-size":k,"--n-label-padding":pe}}),F=n?ft("checkbox",S(()=>a.value[0]),j,e):void 0;return Object.assign(l,g,{rtlEnabled:P,selfRef:t,mergedClsPrefix:o,mergedDisabled:d,renderedChecked:p,mergedTheme:u,labelId:_o(),handleClick:m,handleKeyUp:b,handleKeyDown:x,cssVars:n?void 0:j,themeClass:F==null?void 0:F.themeClass,onRender:F==null?void 0:F.onRender})},render(){var e;const{$slots:t,renderedChecked:o,mergedDisabled:n,indeterminate:r,privateInsideTable:l,cssVars:d,labelId:a,label:i,mergedClsPrefix:s,focusable:v,handleKeyUp:f,handleKeyDown:p,handleClick:u}=this;return(e=this.onRender)===null||e===void 0||e.call(this),c("div",{ref:"selfRef",class:[`${s}-checkbox`,this.themeClass,this.rtlEnabled&&`${s}-checkbox--rtl`,o&&`${s}-checkbox--checked`,n&&`${s}-checkbox--disabled`,r&&`${s}-checkbox--indeterminate`,l&&`${s}-checkbox--inside-table`],tabindex:n||!v?void 0:0,role:"checkbox","aria-checked":r?"mixed":o,"aria-labelledby":a,style:d,onKeyup:f,onKeydown:p,onClick:u,onMousedown:()=>{Nt("selectstart",window,h=>{h.preventDefault()},{once:!0})}},c("div",{class:`${s}-checkbox-box-wrapper`}," ",c("div",{class:`${s}-checkbox-box`},c(Mo,null,{default:()=>this.indeterminate?c("div",{key:"indeterminate",class:`${s}-checkbox-icon`},Sr):c("div",{key:"check",class:`${s}-checkbox-icon`},Rr)}),c("div",{class:`${s}-checkbox-box__border`}))),i!==null||t.default?c("span",{class:`${s}-checkbox__label`,id:a},t.default?t.default():i):null)}});function Mr(e){const{boxShadow2:t}=e;return{menuBoxShadow:t}}const $r=mt({name:"Popselect",common:it,peers:{Popover:Wt,InternalSelectMenu:In},self:Mr}),Xt=$r,Uo=Rt("n-popselect"),Tr=R("popselect-menu",`
 box-shadow: var(--n-menu-box-shadow);
`),Zt={multiple:Boolean,value:{type:[String,Number,Array],default:null},cancelable:Boolean,options:{type:Array,default:()=>[]},size:{type:String,default:"medium"},scrollable:Boolean,"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array],onMouseenter:Function,onMouseleave:Function,renderLabel:Function,showCheckmark:{type:Boolean,default:void 0},nodeProps:Function,virtualScroll:Boolean,onChange:[Function,Array]},fo=gr(Zt),Br=ne({name:"PopselectPanel",props:Zt,setup(e){const t=Te(Uo),{mergedClsPrefixRef:o,inlineThemeDisabled:n}=Be(e),r=Ce("Popselect","-pop-select",Tr,Xt,t.props,o),l=S(()=>$o(e.options,jn("value","children")));function d(p,u){const{onUpdateValue:h,"onUpdate:value":m,onChange:b}=e;h&&D(h,p,u),m&&D(m,p,u),b&&D(b,p,u)}function a(p){s(p.key)}function i(p){jt(p,"action")||p.preventDefault()}function s(p){const{value:{getNode:u}}=l;if(e.multiple)if(Array.isArray(e.value)){const h=[],m=[];let b=!0;e.value.forEach(x=>{if(x===p){b=!1;return}const g=u(x);g&&(h.push(g.key),m.push(g.rawNode))}),b&&(h.push(p),m.push(u(p).rawNode)),d(h,m)}else{const h=u(p);h&&d([p],[h.rawNode])}else if(e.value===p&&e.cancelable)d(null,null);else{const h=u(p);h&&d(p,h.rawNode);const{"onUpdate:show":m,onUpdateShow:b}=t.props;m&&D(m,!1),b&&D(b,!1),t.setShow(!1)}yt(()=>{t.syncPosition()})}Ht(ie(e,"options"),()=>{yt(()=>{t.syncPosition()})});const v=S(()=>{const{self:{menuBoxShadow:p}}=r.value;return{"--n-menu-box-shadow":p}}),f=n?ft("select",void 0,v,t.props):void 0;return{mergedTheme:t.mergedThemeRef,mergedClsPrefix:o,treeMate:l,handleToggle:a,handleMenuMousedown:i,cssVars:n?void 0:v,themeClass:f==null?void 0:f.themeClass,onRender:f==null?void 0:f.onRender}},render(){var e;return(e=this.onRender)===null||e===void 0||e.call(this),c(Nn,{clsPrefix:this.mergedClsPrefix,focusable:!0,nodeProps:this.nodeProps,class:[`${this.mergedClsPrefix}-popselect-menu`,this.themeClass],style:this.cssVars,theme:this.mergedTheme.peers.InternalSelectMenu,themeOverrides:this.mergedTheme.peerOverrides.InternalSelectMenu,multiple:this.multiple,treeMate:this.treeMate,size:this.size,value:this.value,virtualScroll:this.virtualScroll,scrollable:this.scrollable,renderLabel:this.renderLabel,onToggle:this.handleToggle,onMouseenter:this.onMouseenter,onMouseleave:this.onMouseenter,onMousedown:this.handleMenuMousedown,showCheckmark:this.showCheckmark},{action:()=>{var t,o;return((o=(t=this.$slots).action)===null||o===void 0?void 0:o.call(t))||[]},empty:()=>{var t,o;return((o=(t=this.$slots).empty)===null||o===void 0?void 0:o.call(t))||[]}})}}),Or=Object.assign(Object.assign(Object.assign(Object.assign({},Ce.props),Lo(Kt,["showArrow","arrow"])),{placement:Object.assign(Object.assign({},Kt.placement),{default:"bottom"}),trigger:{type:String,default:"hover"}}),Zt),Ar=ne({name:"Popselect",props:Or,inheritAttrs:!1,__popover__:!0,setup(e){const{mergedClsPrefixRef:t}=Be(e),o=Ce("Popselect","-popselect",void 0,Xt,e,t),n=L(null);function r(){var a;(a=n.value)===null||a===void 0||a.syncPosition()}function l(a){var i;(i=n.value)===null||i===void 0||i.setShow(a)}return Ct(Uo,{props:e,mergedThemeRef:o,syncPosition:r,setShow:l}),Object.assign(Object.assign({},{syncPosition:r,setShow:l}),{popoverInstRef:n,mergedTheme:o})},render(){const{mergedTheme:e}=this,t={theme:e.peers.Popover,themeOverrides:e.peerOverrides.Popover,builtinThemeOverrides:{padding:"0"},ref:"popoverInstRef",internalRenderBody:(o,n,r,l,d)=>{const{$attrs:a}=this;return c(Br,Object.assign({},a,{class:[a.class,o],style:[a.style,r]},Kn(this.$props,fo),{ref:hr(n),onMouseenter:ro([l,a.onMouseenter]),onMouseleave:ro([d,a.onMouseleave])}),{action:()=>{var i,s;return(s=(i=this.$slots).action)===null||s===void 0?void 0:s.call(i)},empty:()=>{var i,s;return(s=(i=this.$slots).empty)===null||s===void 0?void 0:s.call(i)}})}};return c(zt,Object.assign({},Lo(this.$props,fo),t,{internalDeactivateImmediately:!0}),{trigger:()=>{var o,n;return(n=(o=this.$slots).default)===null||n===void 0?void 0:n.call(o)}})}}),Lr={itemPaddingSmall:"0 4px",itemMarginSmall:"0 0 0 8px",itemMarginSmallRtl:"0 8px 0 0",itemPaddingMedium:"0 4px",itemMarginMedium:"0 0 0 8px",itemMarginMediumRtl:"0 8px 0 0",itemPaddingLarge:"0 4px",itemMarginLarge:"0 0 0 8px",itemMarginLargeRtl:"0 8px 0 0",buttonIconSizeSmall:"14px",buttonIconSizeMedium:"16px",buttonIconSizeLarge:"18px",inputWidthSmall:"60px",selectWidthSmall:"unset",inputMarginSmall:"0 0 0 8px",inputMarginSmallRtl:"0 8px 0 0",selectMarginSmall:"0 0 0 8px",prefixMarginSmall:"0 8px 0 0",suffixMarginSmall:"0 0 0 8px",inputWidthMedium:"60px",selectWidthMedium:"unset",inputMarginMedium:"0 0 0 8px",inputMarginMediumRtl:"0 8px 0 0",selectMarginMedium:"0 0 0 8px",prefixMarginMedium:"0 8px 0 0",suffixMarginMedium:"0 0 0 8px",inputWidthLarge:"60px",selectWidthLarge:"unset",inputMarginLarge:"0 0 0 8px",inputMarginLargeRtl:"0 8px 0 0",selectMarginLarge:"0 0 0 8px",prefixMarginLarge:"0 8px 0 0",suffixMarginLarge:"0 0 0 8px"},Dr=e=>{const{textColor2:t,primaryColor:o,primaryColorHover:n,primaryColorPressed:r,inputColorDisabled:l,textColorDisabled:d,borderColor:a,borderRadius:i,fontSizeTiny:s,fontSizeSmall:v,fontSizeMedium:f,heightTiny:p,heightSmall:u,heightMedium:h}=e;return Object.assign(Object.assign({},Lr),{buttonColor:"#0000",buttonColorHover:"#0000",buttonColorPressed:"#0000",buttonBorder:`1px solid ${a}`,buttonBorderHover:`1px solid ${a}`,buttonBorderPressed:`1px solid ${a}`,buttonIconColor:t,buttonIconColorHover:t,buttonIconColorPressed:t,itemTextColor:t,itemTextColorHover:n,itemTextColorPressed:r,itemTextColorActive:o,itemTextColorDisabled:d,itemColor:"#0000",itemColorHover:"#0000",itemColorPressed:"#0000",itemColorActive:"#0000",itemColorActiveHover:"#0000",itemColorDisabled:l,itemBorder:"1px solid #0000",itemBorderHover:"1px solid #0000",itemBorderPressed:"1px solid #0000",itemBorderActive:`1px solid ${o}`,itemBorderDisabled:`1px solid ${a}`,itemBorderRadius:i,itemSizeSmall:p,itemSizeMedium:u,itemSizeLarge:h,itemFontSizeSmall:s,itemFontSizeMedium:v,itemFontSizeLarge:f,jumperFontSizeSmall:s,jumperFontSizeMedium:v,jumperFontSizeLarge:f,jumperTextColor:t,jumperTextColorDisabled:d})},Er=mt({name:"Pagination",common:it,peers:{Select:Hn,Input:$n,Popselect:Xt},self:Dr}),Io=Er;function Ur(e,t,o){let n=!1,r=!1,l=1,d=t;if(t===1)return{hasFastBackward:!1,hasFastForward:!1,fastForwardTo:d,fastBackwardTo:l,items:[{type:"page",label:1,active:e===1,mayBeFastBackward:!1,mayBeFastForward:!1}]};if(t===2)return{hasFastBackward:!1,hasFastForward:!1,fastForwardTo:d,fastBackwardTo:l,items:[{type:"page",label:1,active:e===1,mayBeFastBackward:!1,mayBeFastForward:!1},{type:"page",label:2,active:e===2,mayBeFastBackward:!0,mayBeFastForward:!1}]};const a=1,i=t;let s=e,v=e;const f=(o-5)/2;v+=Math.ceil(f),v=Math.min(Math.max(v,a+o-3),i-2),s-=Math.floor(f),s=Math.max(Math.min(s,i-o+3),a+2);let p=!1,u=!1;s>a+2&&(p=!0),v<i-2&&(u=!0);const h=[];h.push({type:"page",label:1,active:e===1,mayBeFastBackward:!1,mayBeFastForward:!1}),p?(n=!0,l=s-1,h.push({type:"fast-backward",active:!1,label:void 0,options:ho(a+1,s-1)})):i>=a+1&&h.push({type:"page",label:a+1,mayBeFastBackward:!0,mayBeFastForward:!1,active:e===a+1});for(let m=s;m<=v;++m)h.push({type:"page",label:m,mayBeFastBackward:!1,mayBeFastForward:!1,active:e===m});return u?(r=!0,d=v+1,h.push({type:"fast-forward",active:!1,label:void 0,options:ho(v+1,i-1)})):v===i-2&&h[h.length-1].label!==i-1&&h.push({type:"page",mayBeFastForward:!0,mayBeFastBackward:!1,label:i-1,active:e===i-1}),h[h.length-1].label!==i&&h.push({type:"page",mayBeFastForward:!1,mayBeFastBackward:!1,label:i,active:e===i}),{hasFastBackward:n,hasFastForward:r,fastBackwardTo:l,fastForwardTo:d,items:h}}function ho(e,t){const o=[];for(let n=e;n<=t;++n)o.push({label:`${n}`,value:n});return o}const po=`
 background: var(--n-item-color-hover);
 color: var(--n-item-text-color-hover);
 border: var(--n-item-border-hover);
`,bo=[O("button",`
 background: var(--n-button-color-hover);
 border: var(--n-button-border-hover);
 color: var(--n-button-icon-color-hover);
 `)],Ir=R("pagination",`
 display: flex;
 vertical-align: middle;
 font-size: var(--n-item-font-size);
 flex-wrap: nowrap;
`,[R("pagination-prefix",`
 display: flex;
 align-items: center;
 margin: var(--n-prefix-margin);
 `),R("pagination-suffix",`
 display: flex;
 align-items: center;
 margin: var(--n-suffix-margin);
 `),U("> *:not(:first-child)",`
 margin: var(--n-item-margin);
 `),R("select",`
 width: var(--n-select-width);
 `),U("&.transition-disabled",[R("pagination-item","transition: none!important;")]),R("pagination-quick-jumper",`
 white-space: nowrap;
 display: flex;
 color: var(--n-jumper-text-color);
 transition: color .3s var(--n-bezier);
 align-items: center;
 font-size: var(--n-jumper-font-size);
 `,[R("input",`
 margin: var(--n-input-margin);
 width: var(--n-input-width);
 `)]),R("pagination-item",`
 position: relative;
 cursor: pointer;
 user-select: none;
 -webkit-user-select: none;
 display: flex;
 align-items: center;
 justify-content: center;
 box-sizing: border-box;
 min-width: var(--n-item-size);
 height: var(--n-item-size);
 padding: var(--n-item-padding);
 background-color: var(--n-item-color);
 color: var(--n-item-text-color);
 border-radius: var(--n-item-border-radius);
 border: var(--n-item-border);
 fill: var(--n-button-icon-color);
 transition:
 color .3s var(--n-bezier),
 border-color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 fill .3s var(--n-bezier);
 `,[O("button",`
 background: var(--n-button-color);
 color: var(--n-button-icon-color);
 border: var(--n-button-border);
 padding: 0;
 `,[R("base-icon",`
 font-size: var(--n-button-icon-size);
 `)]),at("disabled",[O("hover",po,bo),U("&:hover",po,bo),U("&:active",`
 background: var(--n-item-color-pressed);
 color: var(--n-item-text-color-pressed);
 border: var(--n-item-border-pressed);
 `,[O("button",`
 background: var(--n-button-color-pressed);
 border: var(--n-button-border-pressed);
 color: var(--n-button-icon-color-pressed);
 `)]),O("active",`
 background: var(--n-item-color-active);
 color: var(--n-item-text-color-active);
 border: var(--n-item-border-active);
 `,[U("&:hover",`
 background: var(--n-item-color-active-hover);
 `)])]),O("disabled",`
 cursor: not-allowed;
 color: var(--n-item-text-color-disabled);
 `,[O("active, button",`
 background-color: var(--n-item-color-disabled);
 border: var(--n-item-border-disabled);
 `)])]),O("disabled",`
 cursor: not-allowed;
 `,[R("pagination-quick-jumper",`
 color: var(--n-jumper-text-color-disabled);
 `)]),O("simple",`
 display: flex;
 align-items: center;
 flex-wrap: nowrap;
 `,[R("pagination-quick-jumper",[R("input",`
 margin: 0;
 `)])])]),Nr=Object.assign(Object.assign({},Ce.props),{simple:Boolean,page:Number,defaultPage:{type:Number,default:1},itemCount:Number,pageCount:Number,defaultPageCount:{type:Number,default:1},showSizePicker:Boolean,pageSize:Number,defaultPageSize:Number,pageSizes:{type:Array,default(){return[10]}},showQuickJumper:Boolean,size:{type:String,default:"medium"},disabled:Boolean,pageSlot:{type:Number,default:9},selectProps:Object,prev:Function,next:Function,goto:Function,prefix:Function,suffix:Function,label:Function,displayOrder:{type:Array,default:["pages","size-picker","quick-jumper"]},to:Wn.propTo,"onUpdate:page":[Function,Array],onUpdatePage:[Function,Array],"onUpdate:pageSize":[Function,Array],onUpdatePageSize:[Function,Array],onPageSizeChange:[Function,Array],onChange:[Function,Array]}),jr=ne({name:"Pagination",props:Nr,setup(e){const{mergedComponentPropsRef:t,mergedClsPrefixRef:o,inlineThemeDisabled:n,mergedRtlRef:r}=Be(e),l=Ce("Pagination","-pagination",Ir,Io,e,o),{localeRef:d}=To("Pagination"),a=L(null),i=L(e.defaultPage),v=L((()=>{const{defaultPageSize:k}=e;if(k!==void 0)return k;const E=e.pageSizes[0];return typeof E=="number"?E:E.value||10})()),f=Ze(ie(e,"page"),i),p=Ze(ie(e,"pageSize"),v),u=S(()=>{const{itemCount:k}=e;if(k!==void 0)return Math.max(1,Math.ceil(k/p.value));const{pageCount:E}=e;return E!==void 0?Math.max(E,1):1}),h=L("");ht(()=>{e.simple,h.value=String(f.value)});const m=L(!1),b=L(!1),x=L(!1),g=L(!1),P=()=>{e.disabled||(m.value=!0,V())},j=()=>{e.disabled||(m.value=!1,V())},F=()=>{b.value=!0,V()},w=()=>{b.value=!1,V()},_=k=>{W(k)},I=S(()=>Ur(f.value,u.value,e.pageSlot));ht(()=>{I.value.hasFastBackward?I.value.hasFastForward||(m.value=!1,x.value=!1):(b.value=!1,g.value=!1)});const z=S(()=>{const k=d.value.selectionSuffix;return e.pageSizes.map(E=>typeof E=="number"?{label:`${E} / ${k}`,value:E}:E)}),C=S(()=>{var k,E;return((E=(k=t==null?void 0:t.value)===null||k===void 0?void 0:k.Pagination)===null||E===void 0?void 0:E.inputSize)||co(e.size)}),K=S(()=>{var k,E;return((E=(k=t==null?void 0:t.value)===null||k===void 0?void 0:k.Pagination)===null||E===void 0?void 0:E.selectSize)||co(e.size)}),Z=S(()=>(f.value-1)*p.value),Y=S(()=>{const k=f.value*p.value-1,{itemCount:E}=e;return E!==void 0&&k>E-1?E-1:k}),X=S(()=>{const{itemCount:k}=e;return k!==void 0?k:(e.pageCount||1)*p.value}),H=St("Pagination",r,o),V=()=>{yt(()=>{var k;const{value:E}=a;E&&(E.classList.add("transition-disabled"),(k=a.value)===null||k===void 0||k.offsetWidth,E.classList.remove("transition-disabled"))})};function W(k){if(k===f.value)return;const{"onUpdate:page":E,onUpdatePage:Pe,onChange:ke,simple:q}=e;E&&D(E,k),Pe&&D(Pe,k),ke&&D(ke,k),i.value=k,q&&(h.value=String(k))}function te(k){if(k===p.value)return;const{"onUpdate:pageSize":E,onUpdatePageSize:Pe,onPageSizeChange:ke}=e;E&&D(E,k),Pe&&D(Pe,k),ke&&D(ke,k),v.value=k,u.value<f.value&&W(u.value)}function ue(){if(e.disabled)return;const k=Math.min(f.value+1,u.value);W(k)}function y(){if(e.disabled)return;const k=Math.max(f.value-1,1);W(k)}function M(){if(e.disabled)return;const k=Math.min(I.value.fastForwardTo,u.value);W(k)}function A(){if(e.disabled)return;const k=Math.max(I.value.fastBackwardTo,1);W(k)}function T(k){te(k)}function J(){const k=parseInt(h.value);Number.isNaN(k)||(W(Math.max(1,Math.min(k,u.value))),e.simple||(h.value=""))}function ee(){J()}function he(k){if(!e.disabled)switch(k.type){case"page":W(k.label);break;case"fast-backward":A();break;case"fast-forward":M();break}}function pe(k){h.value=k.replace(/\D+/g,"")}ht(()=>{f.value,p.value,V()});const le=S(()=>{const{size:k}=e,{self:{buttonBorder:E,buttonBorderHover:Pe,buttonBorderPressed:ke,buttonIconColor:q,buttonIconColorHover:ae,buttonIconColorPressed:Ae,itemTextColor:we,itemTextColorHover:xe,itemTextColorPressed:tt,itemTextColorActive:ot,itemTextColorDisabled:Me,itemColor:$e,itemColorHover:Ye,itemColorPressed:nt,itemColorActive:Je,itemColorActiveHover:lt,itemColorDisabled:Ke,itemBorder:ge,itemBorderHover:Xe,itemBorderPressed:He,itemBorderActive:Le,itemBorderDisabled:$,itemBorderRadius:G,jumperTextColor:B,jumperTextColorDisabled:N,buttonColor:ce,buttonColorHover:Re,buttonColorPressed:Oe,[fe("itemPadding",k)]:Fe,[fe("itemMargin",k)]:Ve,[fe("inputWidth",k)]:We,[fe("selectWidth",k)]:Qe,[fe("inputMargin",k)]:st,[fe("selectMargin",k)]:rt,[fe("jumperFontSize",k)]:De,[fe("prefixMargin",k)]:Se,[fe("suffixMargin",k)]:_e,[fe("itemSize",k)]:Pt,[fe("buttonIconSize",k)]:Ft,[fe("itemFontSize",k)]:_t,[`${fe("itemMargin",k)}Rtl`]:Mt,[`${fe("inputMargin",k)}Rtl`]:$t},common:{cubicBezierEaseInOut:Tt}}=l.value;return{"--n-prefix-margin":Se,"--n-suffix-margin":_e,"--n-item-font-size":_t,"--n-select-width":Qe,"--n-select-margin":rt,"--n-input-width":We,"--n-input-margin":st,"--n-input-margin-rtl":$t,"--n-item-size":Pt,"--n-item-text-color":we,"--n-item-text-color-disabled":Me,"--n-item-text-color-hover":xe,"--n-item-text-color-active":ot,"--n-item-text-color-pressed":tt,"--n-item-color":$e,"--n-item-color-hover":Ye,"--n-item-color-disabled":Ke,"--n-item-color-active":Je,"--n-item-color-active-hover":lt,"--n-item-color-pressed":nt,"--n-item-border":ge,"--n-item-border-hover":Xe,"--n-item-border-disabled":$,"--n-item-border-active":Le,"--n-item-border-pressed":He,"--n-item-padding":Fe,"--n-item-border-radius":G,"--n-bezier":Tt,"--n-jumper-font-size":De,"--n-jumper-text-color":B,"--n-jumper-text-color-disabled":N,"--n-item-margin":Ve,"--n-item-margin-rtl":Mt,"--n-button-icon-size":Ft,"--n-button-icon-color":q,"--n-button-icon-color-hover":ae,"--n-button-icon-color-pressed":Ae,"--n-button-color-hover":Re,"--n-button-color":ce,"--n-button-color-pressed":Oe,"--n-button-border":E,"--n-button-border-hover":Pe,"--n-button-border-pressed":ke}}),de=n?ft("pagination",S(()=>{let k="";const{size:E}=e;return k+=E[0],k}),le,e):void 0;return{rtlEnabled:H,mergedClsPrefix:o,locale:d,selfRef:a,mergedPage:f,pageItems:S(()=>I.value.items),mergedItemCount:X,jumperValue:h,pageSizeOptions:z,mergedPageSize:p,inputSize:C,selectSize:K,mergedTheme:l,mergedPageCount:u,startIndex:Z,endIndex:Y,showFastForwardMenu:x,showFastBackwardMenu:g,fastForwardActive:m,fastBackwardActive:b,handleMenuSelect:_,handleFastForwardMouseenter:P,handleFastForwardMouseleave:j,handleFastBackwardMouseenter:F,handleFastBackwardMouseleave:w,handleJumperInput:pe,handleBackwardClick:y,handleForwardClick:ue,handlePageItemClick:he,handleSizePickerChange:T,handleQuickJumperChange:ee,cssVars:n?void 0:le,themeClass:de==null?void 0:de.themeClass,onRender:de==null?void 0:de.onRender}},render(){const{$slots:e,mergedClsPrefix:t,disabled:o,cssVars:n,mergedPage:r,mergedPageCount:l,pageItems:d,showSizePicker:a,showQuickJumper:i,mergedTheme:s,locale:v,inputSize:f,selectSize:p,mergedPageSize:u,pageSizeOptions:h,jumperValue:m,simple:b,prev:x,next:g,prefix:P,suffix:j,label:F,goto:w,handleJumperInput:_,handleSizePickerChange:I,handleBackwardClick:z,handlePageItemClick:C,handleForwardClick:K,handleQuickJumperChange:Z,onRender:Y}=this;Y==null||Y();const X=e.prefix||P,H=e.suffix||j,V=x||e.prev,W=g||e.next,te=F||e.label;return c("div",{ref:"selfRef",class:[`${t}-pagination`,this.themeClass,this.rtlEnabled&&`${t}-pagination--rtl`,o&&`${t}-pagination--disabled`,b&&`${t}-pagination--simple`],style:n},X?c("div",{class:`${t}-pagination-prefix`},X({page:r,pageSize:u,pageCount:l,startIndex:this.startIndex,endIndex:this.endIndex,itemCount:this.mergedItemCount})):null,this.displayOrder.map(ue=>{switch(ue){case"pages":return c(ze,null,c("div",{class:[`${t}-pagination-item`,!V&&`${t}-pagination-item--button`,(r<=1||r>l||o)&&`${t}-pagination-item--disabled`],onClick:z},V?V({page:r,pageSize:u,pageCount:l,startIndex:this.startIndex,endIndex:this.endIndex,itemCount:this.mergedItemCount}):c(qe,{clsPrefix:t},{default:()=>this.rtlEnabled?c(Qt,null):c(eo,null)})),b?c(ze,null,c("div",{class:`${t}-pagination-quick-jumper`},c(Jt,{value:m,onUpdateValue:_,size:f,placeholder:"",disabled:o,theme:s.peers.Input,themeOverrides:s.peerOverrides.Input,onChange:Z}))," / ",l):d.map((y,M)=>{let A,T,J;const{type:ee}=y;switch(ee){case"page":const pe=y.label;te?A=te({type:"page",node:pe,active:y.active}):A=pe;break;case"fast-forward":const le=this.fastForwardActive?c(qe,{clsPrefix:t},{default:()=>this.rtlEnabled?c(oo,null):c(to,null)}):c(qe,{clsPrefix:t},{default:()=>c(uo,null)});te?A=te({type:"fast-forward",node:le,active:this.fastForwardActive||this.showFastForwardMenu}):A=le,T=this.handleFastForwardMouseenter,J=this.handleFastForwardMouseleave;break;case"fast-backward":const de=this.fastBackwardActive?c(qe,{clsPrefix:t},{default:()=>this.rtlEnabled?c(to,null):c(oo,null)}):c(qe,{clsPrefix:t},{default:()=>c(uo,null)});te?A=te({type:"fast-backward",node:de,active:this.fastBackwardActive||this.showFastBackwardMenu}):A=de,T=this.handleFastBackwardMouseenter,J=this.handleFastBackwardMouseleave;break}const he=c("div",{key:M,class:[`${t}-pagination-item`,y.active&&`${t}-pagination-item--active`,ee!=="page"&&(ee==="fast-backward"&&this.showFastBackwardMenu||ee==="fast-forward"&&this.showFastForwardMenu)&&`${t}-pagination-item--hover`,o&&`${t}-pagination-item--disabled`,ee==="page"&&`${t}-pagination-item--clickable`],onClick:()=>{C(y)},onMouseenter:T,onMouseleave:J},A);if(ee==="page"&&!y.mayBeFastBackward&&!y.mayBeFastForward)return he;{const pe=y.type==="page"?y.mayBeFastBackward?"fast-backward":"fast-forward":y.type;return c(Ar,{to:this.to,key:pe,disabled:o,trigger:"hover",virtualScroll:!0,style:{width:"60px"},theme:s.peers.Popselect,themeOverrides:s.peerOverrides.Popselect,builtinThemeOverrides:{peers:{InternalSelectMenu:{height:"calc(var(--n-option-height) * 4.6)"}}},nodeProps:()=>({style:{justifyContent:"center"}}),show:ee==="page"?!1:ee==="fast-backward"?this.showFastBackwardMenu:this.showFastForwardMenu,onUpdateShow:le=>{ee!=="page"&&(le?ee==="fast-backward"?this.showFastBackwardMenu=le:this.showFastForwardMenu=le:(this.showFastBackwardMenu=!1,this.showFastForwardMenu=!1))},options:y.type!=="page"?y.options:[],onUpdateValue:this.handleMenuSelect,scrollable:!0,showCheckmark:!1},{default:()=>he})}}),c("div",{class:[`${t}-pagination-item`,!W&&`${t}-pagination-item--button`,{[`${t}-pagination-item--disabled`]:r<1||r>=l||o}],onClick:K},W?W({page:r,pageSize:u,pageCount:l,itemCount:this.mergedItemCount,startIndex:this.startIndex,endIndex:this.endIndex}):c(qe,{clsPrefix:t},{default:()=>this.rtlEnabled?c(eo,null):c(Qt,null)})));case"size-picker":return!b&&a?c(Vn,Object.assign({consistentMenuWidth:!1,placeholder:"",showCheckmark:!1,to:this.to},this.selectProps,{size:p,options:h,value:u,disabled:o,theme:s.peers.Select,themeOverrides:s.peerOverrides.Select,onUpdateValue:I})):null;case"quick-jumper":return!b&&i?c("div",{class:`${t}-pagination-quick-jumper`},w?w():qt(this.$slots.goto,()=>[v.goto]),c(Jt,{value:m,onUpdateValue:_,size:f,placeholder:"",disabled:o,theme:s.peers.Input,themeOverrides:s.peerOverrides.Input,onChange:Z})):null;default:return null}}),H?c("div",{class:`${t}-pagination-suffix`},H({page:r,pageSize:u,pageCount:l,startIndex:this.startIndex,endIndex:this.endIndex,itemCount:this.mergedItemCount})):null)}}),Kr={padding:"8px 14px"},Hr=e=>{const{borderRadius:t,boxShadow2:o,baseColor:n}=e;return Object.assign(Object.assign({},Kr),{borderRadius:t,boxShadow:o,color:ve(n,"rgba(0, 0, 0, .85)"),textColor:n})},Vr=mt({name:"Tooltip",common:it,peers:{Popover:Wt},self:Hr}),No=Vr,Wr=mt({name:"Ellipsis",common:it,peers:{Tooltip:No}}),jo=Wr,qr={radioSizeSmall:"14px",radioSizeMedium:"16px",radioSizeLarge:"18px",labelPadding:"0 8px",labelFontWeight:"400"},Gr=e=>{const{borderColor:t,primaryColor:o,baseColor:n,textColorDisabled:r,inputColorDisabled:l,textColor2:d,opacityDisabled:a,borderRadius:i,fontSizeSmall:s,fontSizeMedium:v,fontSizeLarge:f,heightSmall:p,heightMedium:u,heightLarge:h,lineHeight:m}=e;return Object.assign(Object.assign({},qr),{labelLineHeight:m,buttonHeightSmall:p,buttonHeightMedium:u,buttonHeightLarge:h,fontSizeSmall:s,fontSizeMedium:v,fontSizeLarge:f,boxShadow:`inset 0 0 0 1px ${t}`,boxShadowActive:`inset 0 0 0 1px ${o}`,boxShadowFocus:`inset 0 0 0 1px ${o}, 0 0 0 2px ${It(o,{alpha:.2})}`,boxShadowHover:`inset 0 0 0 1px ${o}`,boxShadowDisabled:`inset 0 0 0 1px ${t}`,color:n,colorDisabled:l,colorActive:"#0000",textColor:d,textColorDisabled:r,dotColorActive:o,dotColorDisabled:t,buttonBorderColor:t,buttonBorderColorActive:o,buttonBorderColorHover:t,buttonColor:n,buttonColorActive:n,buttonTextColor:d,buttonTextColorActive:o,buttonTextColorHover:o,opacityDisabled:a,buttonBoxShadowFocus:`inset 0 0 0 1px ${o}, 0 0 0 2px ${It(o,{alpha:.3})}`,buttonBoxShadowHover:"inset 0 0 0 1px #0000",buttonBoxShadow:"inset 0 0 0 1px #0000",buttonBorderRadius:i})},Xr={name:"Radio",common:it,self:Gr},Yt=Xr,Zr={thPaddingSmall:"8px",thPaddingMedium:"12px",thPaddingLarge:"12px",tdPaddingSmall:"8px",tdPaddingMedium:"12px",tdPaddingLarge:"12px",sorterSize:"15px",resizableContainerSize:"8px",resizableSize:"2px",filterSize:"15px",paginationMargin:"12px 0 0 0",emptyPadding:"48px 0",actionPadding:"8px 12px",actionButtonMargin:"0 8px 0 0"},Yr=e=>{const{cardColor:t,modalColor:o,popoverColor:n,textColor2:r,textColor1:l,tableHeaderColor:d,tableColorHover:a,iconColor:i,primaryColor:s,fontWeightStrong:v,borderRadius:f,lineHeight:p,fontSizeSmall:u,fontSizeMedium:h,fontSizeLarge:m,dividerColor:b,heightSmall:x,opacityDisabled:g,tableColorStriped:P}=e;return Object.assign(Object.assign({},Zr),{actionDividerColor:b,lineHeight:p,borderRadius:f,fontSizeSmall:u,fontSizeMedium:h,fontSizeLarge:m,borderColor:ve(t,b),tdColorHover:ve(t,a),tdColorStriped:ve(t,P),thColor:ve(t,d),thColorHover:ve(ve(t,d),a),tdColor:t,tdTextColor:r,thTextColor:l,thFontWeight:v,thButtonColorHover:a,thIconColor:i,thIconColorActive:s,borderColorModal:ve(o,b),tdColorHoverModal:ve(o,a),tdColorStripedModal:ve(o,P),thColorModal:ve(o,d),thColorHoverModal:ve(ve(o,d),a),tdColorModal:o,borderColorPopover:ve(n,b),tdColorHoverPopover:ve(n,a),tdColorStripedPopover:ve(n,P),thColorPopover:ve(n,d),thColorHoverPopover:ve(ve(n,d),a),tdColorPopover:n,boxShadowBefore:"inset -12px 0 8px -12px rgba(0, 0, 0, .18)",boxShadowAfter:"inset 12px 0 8px -12px rgba(0, 0, 0, .18)",loadingColor:s,loadingSize:x,opacityLoading:g})},Jr=mt({name:"DataTable",common:it,peers:{Button:Tn,Checkbox:Do,Radio:Yt,Pagination:Io,Scrollbar:qn,Empty:Gn,Popover:Wt,Ellipsis:jo,Dropdown:pr},self:Yr}),Qr=Jr,ea=Object.assign(Object.assign({},Kt),Ce.props),ta=ne({name:"Tooltip",props:ea,__popover__:!0,setup(e){const{mergedClsPrefixRef:t}=Be(e),o=Ce("Tooltip","-tooltip",void 0,No,e,t),n=L(null);return Object.assign(Object.assign({},{syncPosition(){n.value.syncPosition()},setShow(l){n.value.setShow(l)}}),{popoverRef:n,mergedTheme:o,popoverThemeOverrides:S(()=>o.value.self)})},render(){const{mergedTheme:e,internalExtraClass:t}=this;return c(zt,Object.assign(Object.assign({},this.$props),{theme:e.peers.Popover,themeOverrides:e.peerOverrides.Popover,builtinThemeOverrides:this.popoverThemeOverrides,internalExtraClass:t.concat("tooltip"),ref:"popoverRef"}),this.$slots)}}),oa=R("ellipsis",{overflow:"hidden"},[at("line-clamp",`
 white-space: nowrap;
 display: inline-block;
 vertical-align: bottom;
 max-width: 100%;
 `),O("line-clamp",`
 display: -webkit-inline-box;
 -webkit-box-orient: vertical;
 `),O("cursor-pointer",`
 cursor: pointer;
 `)]);function mo(e){return`${e}-ellipsis--line-clamp`}function vo(e,t){return`${e}-ellipsis--cursor-${t}`}const na=Object.assign(Object.assign({},Ce.props),{expandTrigger:String,lineClamp:[Number,String],tooltip:{type:[Boolean,Object],default:!0}}),Ko=ne({name:"Ellipsis",inheritAttrs:!1,props:na,setup(e,{slots:t,attrs:o}){const{mergedClsPrefixRef:n}=Be(e),r=Ce("Ellipsis","-ellipsis",oa,jo,e,n),l=L(null),d=L(null),a=L(null),i=L(!1),s=S(()=>{const{lineClamp:b}=e,{value:x}=i;return b!==void 0?{textOverflow:"","-webkit-line-clamp":x?"":b}:{textOverflow:x?"":"ellipsis","-webkit-line-clamp":""}});function v(){let b=!1;const{value:x}=i;if(x)return!0;const{value:g}=l;if(g){const{lineClamp:P}=e;if(u(g),P!==void 0)b=g.scrollHeight<=g.offsetHeight;else{const{value:j}=d;j&&(b=j.getBoundingClientRect().width<=g.getBoundingClientRect().width)}h(g,b)}return b}const f=S(()=>e.expandTrigger==="click"?()=>{var b;const{value:x}=i;x&&((b=a.value)===null||b===void 0||b.setShow(!1)),i.value=!x}:void 0);So(()=>{var b;e.tooltip&&((b=a.value)===null||b===void 0||b.setShow(!1))});const p=()=>c("span",Object.assign({},Vt(o,{class:[`${n.value}-ellipsis`,e.lineClamp!==void 0?mo(n.value):void 0,e.expandTrigger==="click"?vo(n.value,"pointer"):void 0],style:s.value}),{ref:"triggerRef",onClick:f.value,onMouseenter:e.expandTrigger==="click"?v:void 0}),e.lineClamp?t:c("span",{ref:"triggerInnerRef"},t));function u(b){if(!b)return;const x=s.value,g=mo(n.value);e.lineClamp!==void 0?m(b,g,"add"):m(b,g,"remove");for(const P in x)b.style[P]!==x[P]&&(b.style[P]=x[P])}function h(b,x){const g=vo(n.value,"pointer");e.expandTrigger==="click"&&!x?m(b,g,"add"):m(b,g,"remove")}function m(b,x,g){g==="add"?b.classList.contains(x)||b.classList.add(x):b.classList.contains(x)&&b.classList.remove(x)}return{mergedTheme:r,triggerRef:l,triggerInnerRef:d,tooltipRef:a,handleClick:f,renderTrigger:p,getTooltipDisabled:v}},render(){var e;const{tooltip:t,renderTrigger:o,$slots:n}=this;if(t){const{mergedTheme:r}=this;return c(ta,Object.assign({ref:"tooltipRef",placement:"top"},t,{getDisabled:this.getTooltipDisabled,theme:r.peers.Tooltip,themeOverrides:r.peerOverrides.Tooltip}),{trigger:o,default:(e=n.tooltip)!==null&&e!==void 0?e:n.default})}else return o()}}),ra=ne({name:"DataTableRenderSorter",props:{render:{type:Function,required:!0},order:{type:[String,Boolean],default:!1}},render(){const{render:e,order:t}=this;return e({order:t})}}),aa=Object.assign(Object.assign({},Ce.props),{onUnstableColumnResize:Function,pagination:{type:[Object,Boolean],default:!1},paginateSinglePage:{type:Boolean,default:!0},minHeight:[Number,String],maxHeight:[Number,String],columns:{type:Array,default:()=>[]},rowClassName:[String,Function],rowProps:Function,rowKey:Function,summary:[Function],data:{type:Array,default:()=>[]},loading:Boolean,bordered:{type:Boolean,default:void 0},bottomBordered:{type:Boolean,default:void 0},striped:Boolean,scrollX:[Number,String],defaultCheckedRowKeys:{type:Array,default:()=>[]},checkedRowKeys:Array,singleLine:{type:Boolean,default:!0},singleColumn:Boolean,size:{type:String,default:"medium"},remote:Boolean,defaultExpandedRowKeys:{type:Array,default:[]},defaultExpandAll:Boolean,expandedRowKeys:Array,stickyExpandedRows:Boolean,virtualScroll:Boolean,tableLayout:{type:String,default:"auto"},allowCheckingNotLoaded:Boolean,cascade:{type:Boolean,default:!0},childrenKey:{type:String,default:"children"},indent:{type:Number,default:16},flexHeight:Boolean,summaryPlacement:{type:String,default:"bottom"},paginationBehaviorOnFilter:{type:String,default:"current"},scrollbarProps:Object,renderCell:Function,renderExpandIcon:Function,spinProps:{type:Object,default:{}},onLoad:Function,"onUpdate:page":[Function,Array],onUpdatePage:[Function,Array],"onUpdate:pageSize":[Function,Array],onUpdatePageSize:[Function,Array],"onUpdate:sorter":[Function,Array],onUpdateSorter:[Function,Array],"onUpdate:filters":[Function,Array],onUpdateFilters:[Function,Array],"onUpdate:checkedRowKeys":[Function,Array],onUpdateCheckedRowKeys:[Function,Array],"onUpdate:expandedRowKeys":[Function,Array],onUpdateExpandedRowKeys:[Function,Array],onScroll:Function,onPageChange:[Function,Array],onPageSizeChange:[Function,Array],onSorterChange:[Function,Array],onFiltersChange:[Function,Array],onCheckedRowKeysChange:[Function,Array]}),je=Rt("n-data-table"),ia=ne({name:"SortIcon",props:{column:{type:Object,required:!0}},setup(e){const{mergedComponentPropsRef:t}=Be(),{mergedSortStateRef:o,mergedClsPrefixRef:n}=Te(je),r=S(()=>o.value.find(i=>i.columnKey===e.column.key)),l=S(()=>r.value!==void 0),d=S(()=>{const{value:i}=r;return i&&l.value?i.order:!1}),a=S(()=>{var i,s;return((s=(i=t==null?void 0:t.value)===null||i===void 0?void 0:i.DataTable)===null||s===void 0?void 0:s.renderSorter)||e.column.renderSorter});return{mergedClsPrefix:n,active:l,mergedSortOrder:d,mergedRenderSorter:a}},render(){const{mergedRenderSorter:e,mergedSortOrder:t,mergedClsPrefix:o}=this,{renderSorterIcon:n}=this.column;return e?c(ra,{render:e,order:t}):c("span",{class:[`${o}-data-table-sorter`,t==="ascend"&&`${o}-data-table-sorter--asc`,t==="descend"&&`${o}-data-table-sorter--desc`]},n?n({order:t}):c(qe,{clsPrefix:o},{default:()=>c(xr,null)}))}}),la=ne({name:"DataTableRenderFilter",props:{render:{type:Function,required:!0},active:{type:Boolean,default:!1},show:{type:Boolean,default:!1}},render(){const{render:e,active:t,show:o}=this;return e({active:t,show:o})}}),sa={name:String,value:{type:[String,Number,Boolean],default:"on"},checked:{type:Boolean,default:void 0},defaultChecked:Boolean,disabled:{type:Boolean,default:void 0},label:String,size:String,onUpdateChecked:[Function,Array],"onUpdate:checked":[Function,Array],checkedValue:{type:Boolean,default:void 0}},Ho=Rt("n-radio-group");function da(e){const t=wt(e,{mergedSize(g){const{size:P}=e;if(P!==void 0)return P;if(d){const{mergedSizeRef:{value:j}}=d;if(j!==void 0)return j}return g?g.mergedSize.value:"medium"},mergedDisabled(g){return!!(e.disabled||d!=null&&d.disabledRef.value||g!=null&&g.disabled.value)}}),{mergedSizeRef:o,mergedDisabledRef:n}=t,r=L(null),l=L(null),d=Te(Ho,null),a=L(e.defaultChecked),i=ie(e,"checked"),s=Ze(i,a),v=Ge(()=>d?d.valueRef.value===e.value:s.value),f=Ge(()=>{const{name:g}=e;if(g!==void 0)return g;if(d)return d.nameRef.value}),p=L(!1);function u(){if(d){const{doUpdateValue:g}=d,{value:P}=e;D(g,P)}else{const{onUpdateChecked:g,"onUpdate:checked":P}=e,{nTriggerFormInput:j,nTriggerFormChange:F}=t;g&&D(g,!0),P&&D(P,!0),j(),F(),a.value=!0}}function h(){n.value||v.value||u()}function m(){h()}function b(){p.value=!1}function x(){p.value=!0}return{mergedClsPrefix:d?d.mergedClsPrefixRef:Be(e).mergedClsPrefixRef,inputRef:r,labelRef:l,mergedName:f,mergedDisabled:n,uncontrolledChecked:a,renderSafeChecked:v,focus:p,mergedSize:o,handleRadioInputChange:m,handleRadioInputBlur:b,handleRadioInputFocus:x}}const ca=R("radio",`
 line-height: var(--n-label-line-height);
 outline: none;
 position: relative;
 user-select: none;
 -webkit-user-select: none;
 display: inline-flex;
 align-items: flex-start;
 flex-wrap: nowrap;
 font-size: var(--n-font-size);
 word-break: break-word;
`,[O("checked",[oe("dot",`
 background-color: var(--n-color-active);
 `)]),oe("dot-wrapper",`
 position: relative;
 flex-shrink: 0;
 flex-grow: 0;
 width: var(--n-radio-size);
 `),R("radio-input",`
 position: absolute;
 border: 0;
 border-radius: inherit;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 opacity: 0;
 z-index: 1;
 cursor: pointer;
 `),oe("dot",`
 position: absolute;
 top: 50%;
 left: 0;
 transform: translateY(-50%);
 height: var(--n-radio-size);
 width: var(--n-radio-size);
 background: var(--n-color);
 box-shadow: var(--n-box-shadow);
 border-radius: 50%;
 transition:
 background-color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 `,[U("&::before",`
 content: "";
 opacity: 0;
 position: absolute;
 left: 4px;
 top: 4px;
 height: calc(100% - 8px);
 width: calc(100% - 8px);
 border-radius: 50%;
 transform: scale(.8);
 background: var(--n-dot-color-active);
 transition: 
 opacity .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 transform .3s var(--n-bezier);
 `),O("checked",{boxShadow:"var(--n-box-shadow-active)"},[U("&::before",`
 opacity: 1;
 transform: scale(1);
 `)])]),oe("label",`
 color: var(--n-text-color);
 padding: var(--n-label-padding);
 font-weight: var(--n-label-font-weight);
 display: inline-block;
 transition: color .3s var(--n-bezier);
 `),at("disabled",`
 cursor: pointer;
 `,[U("&:hover",[oe("dot",{boxShadow:"var(--n-box-shadow-hover)"})]),O("focus",[U("&:not(:active)",[oe("dot",{boxShadow:"var(--n-box-shadow-focus)"})])])]),O("disabled",`
 cursor: not-allowed;
 `,[oe("dot",{boxShadow:"var(--n-box-shadow-disabled)",backgroundColor:"var(--n-color-disabled)"},[U("&::before",{backgroundColor:"var(--n-dot-color-disabled)"}),O("checked",`
 opacity: 1;
 `)]),oe("label",{color:"var(--n-text-color-disabled)"}),R("radio-input",`
 cursor: not-allowed;
 `)])]),Vo=ne({name:"Radio",props:Object.assign(Object.assign({},Ce.props),sa),setup(e){const t=da(e),o=Ce("Radio","-radio",ca,Yt,e,t.mergedClsPrefix),n=S(()=>{const{mergedSize:{value:s}}=t,{common:{cubicBezierEaseInOut:v},self:{boxShadow:f,boxShadowActive:p,boxShadowDisabled:u,boxShadowFocus:h,boxShadowHover:m,color:b,colorDisabled:x,colorActive:g,textColor:P,textColorDisabled:j,dotColorActive:F,dotColorDisabled:w,labelPadding:_,labelLineHeight:I,labelFontWeight:z,[fe("fontSize",s)]:C,[fe("radioSize",s)]:K}}=o.value;return{"--n-bezier":v,"--n-label-line-height":I,"--n-label-font-weight":z,"--n-box-shadow":f,"--n-box-shadow-active":p,"--n-box-shadow-disabled":u,"--n-box-shadow-focus":h,"--n-box-shadow-hover":m,"--n-color":b,"--n-color-active":g,"--n-color-disabled":x,"--n-dot-color-active":F,"--n-dot-color-disabled":w,"--n-font-size":C,"--n-radio-size":K,"--n-text-color":P,"--n-text-color-disabled":j,"--n-label-padding":_}}),{inlineThemeDisabled:r,mergedClsPrefixRef:l,mergedRtlRef:d}=Be(e),a=St("Radio",d,l),i=r?ft("radio",S(()=>t.mergedSize.value[0]),n,e):void 0;return Object.assign(t,{rtlEnabled:a,cssVars:r?void 0:n,themeClass:i==null?void 0:i.themeClass,onRender:i==null?void 0:i.onRender})},render(){const{$slots:e,mergedClsPrefix:t,onRender:o,label:n}=this;return o==null||o(),c("label",{class:[`${t}-radio`,this.themeClass,{[`${t}-radio--rtl`]:this.rtlEnabled,[`${t}-radio--disabled`]:this.mergedDisabled,[`${t}-radio--checked`]:this.renderSafeChecked,[`${t}-radio--focus`]:this.focus}],style:this.cssVars},c("input",{ref:"inputRef",type:"radio",class:`${t}-radio-input`,value:this.value,name:this.mergedName,checked:this.renderSafeChecked,disabled:this.mergedDisabled,onChange:this.handleRadioInputChange,onFocus:this.handleRadioInputFocus,onBlur:this.handleRadioInputBlur}),c("div",{class:`${t}-radio__dot-wrapper`}," ",c("div",{class:[`${t}-radio__dot`,this.renderSafeChecked&&`${t}-radio__dot--checked`]})),Xn(e.default,r=>!r&&!n?null:c("div",{ref:"labelRef",class:`${t}-radio__label`},r||n)))}}),ua=R("radio-group",`
 display: inline-block;
 font-size: var(--n-font-size);
`,[oe("splitor",`
 display: inline-block;
 vertical-align: bottom;
 width: 1px;
 transition:
 background-color .3s var(--n-bezier),
 opacity .3s var(--n-bezier);
 background: var(--n-button-border-color);
 `,[O("checked",{backgroundColor:"var(--n-button-border-color-active)"}),O("disabled",{opacity:"var(--n-opacity-disabled)"})]),O("button-group",`
 white-space: nowrap;
 height: var(--n-height);
 line-height: var(--n-height);
 `,[R("radio-button",{height:"var(--n-height)",lineHeight:"var(--n-height)"}),oe("splitor",{height:"var(--n-height)"})]),R("radio-button",`
 vertical-align: bottom;
 outline: none;
 position: relative;
 user-select: none;
 -webkit-user-select: none;
 display: inline-block;
 box-sizing: border-box;
 padding-left: 14px;
 padding-right: 14px;
 white-space: nowrap;
 transition:
 background-color .3s var(--n-bezier),
 opacity .3s var(--n-bezier),
 border-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 color: var(--n-button-text-color);
 border-top: 1px solid var(--n-button-border-color);
 border-bottom: 1px solid var(--n-button-border-color);
 `,[R("radio-input",`
 pointer-events: none;
 position: absolute;
 border: 0;
 border-radius: inherit;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 opacity: 0;
 z-index: 1;
 `),oe("state-border",`
 z-index: 1;
 pointer-events: none;
 position: absolute;
 box-shadow: var(--n-button-box-shadow);
 transition: box-shadow .3s var(--n-bezier);
 left: -1px;
 bottom: -1px;
 right: -1px;
 top: -1px;
 `),U("&:first-child",`
 border-top-left-radius: var(--n-button-border-radius);
 border-bottom-left-radius: var(--n-button-border-radius);
 border-left: 1px solid var(--n-button-border-color);
 `,[oe("state-border",`
 border-top-left-radius: var(--n-button-border-radius);
 border-bottom-left-radius: var(--n-button-border-radius);
 `)]),U("&:last-child",`
 border-top-right-radius: var(--n-button-border-radius);
 border-bottom-right-radius: var(--n-button-border-radius);
 border-right: 1px solid var(--n-button-border-color);
 `,[oe("state-border",`
 border-top-right-radius: var(--n-button-border-radius);
 border-bottom-right-radius: var(--n-button-border-radius);
 `)]),at("disabled",`
 cursor: pointer;
 `,[U("&:hover",[oe("state-border",`
 transition: box-shadow .3s var(--n-bezier);
 box-shadow: var(--n-button-box-shadow-hover);
 `),at("checked",{color:"var(--n-button-text-color-hover)"})]),O("focus",[U("&:not(:active)",[oe("state-border",{boxShadow:"var(--n-button-box-shadow-focus)"})])])]),O("checked",`
 background: var(--n-button-color-active);
 color: var(--n-button-text-color-active);
 border-color: var(--n-button-border-color-active);
 `),O("disabled",`
 cursor: not-allowed;
 opacity: var(--n-opacity-disabled);
 `)])]);function fa(e,t,o){var n;const r=[];let l=!1;for(let d=0;d<e.length;++d){const a=e[d],i=(n=a.type)===null||n===void 0?void 0:n.name;i==="RadioButton"&&(l=!0);const s=a.props;if(i!=="RadioButton"){r.push(a);continue}if(d===0)r.push(a);else{const v=r[r.length-1].props,f=t===v.value,p=v.disabled,u=t===s.value,h=s.disabled,m=(f?2:0)+(p?0:1),b=(u?2:0)+(h?0:1),x={[`${o}-radio-group__splitor--disabled`]:p,[`${o}-radio-group__splitor--checked`]:f},g={[`${o}-radio-group__splitor--disabled`]:h,[`${o}-radio-group__splitor--checked`]:u},P=m<b?g:x;r.push(c("div",{class:[`${o}-radio-group__splitor`,P]}),a)}}return{children:r,isButtonGroup:l}}const ha=Object.assign(Object.assign({},Ce.props),{name:String,value:[String,Number,Boolean],defaultValue:{type:[String,Number,Boolean],default:null},size:String,disabled:{type:Boolean,default:void 0},"onUpdate:value":[Function,Array],onUpdateValue:[Function,Array]}),pa=ne({name:"RadioGroup",props:ha,setup(e){const t=L(null),{mergedSizeRef:o,mergedDisabledRef:n,nTriggerFormChange:r,nTriggerFormInput:l,nTriggerFormBlur:d,nTriggerFormFocus:a}=wt(e),{mergedClsPrefixRef:i,inlineThemeDisabled:s,mergedRtlRef:v}=Be(e),f=Ce("Radio","-radio-group",ua,Yt,e,i),p=L(e.defaultValue),u=ie(e,"value"),h=Ze(u,p);function m(F){const{onUpdateValue:w,"onUpdate:value":_}=e;w&&D(w,F),_&&D(_,F),p.value=F,r(),l()}function b(F){const{value:w}=t;w&&(w.contains(F.relatedTarget)||a())}function x(F){const{value:w}=t;w&&(w.contains(F.relatedTarget)||d())}Ct(Ho,{mergedClsPrefixRef:i,nameRef:ie(e,"name"),valueRef:h,disabledRef:n,mergedSizeRef:o,doUpdateValue:m});const g=St("Radio",v,i),P=S(()=>{const{value:F}=o,{common:{cubicBezierEaseInOut:w},self:{buttonBorderColor:_,buttonBorderColorActive:I,buttonBorderRadius:z,buttonBoxShadow:C,buttonBoxShadowFocus:K,buttonBoxShadowHover:Z,buttonColorActive:Y,buttonTextColor:X,buttonTextColorActive:H,buttonTextColorHover:V,opacityDisabled:W,[fe("buttonHeight",F)]:te,[fe("fontSize",F)]:ue}}=f.value;return{"--n-font-size":ue,"--n-bezier":w,"--n-button-border-color":_,"--n-button-border-color-active":I,"--n-button-border-radius":z,"--n-button-box-shadow":C,"--n-button-box-shadow-focus":K,"--n-button-box-shadow-hover":Z,"--n-button-color-active":Y,"--n-button-text-color":X,"--n-button-text-color-hover":V,"--n-button-text-color-active":H,"--n-height":te,"--n-opacity-disabled":W}}),j=s?ft("radio-group",S(()=>o.value[0]),P,e):void 0;return{selfElRef:t,rtlEnabled:g,mergedClsPrefix:i,mergedValue:h,handleFocusout:x,handleFocusin:b,cssVars:s?void 0:P,themeClass:j==null?void 0:j.themeClass,onRender:j==null?void 0:j.onRender}},render(){var e;const{mergedValue:t,mergedClsPrefix:o,handleFocusin:n,handleFocusout:r}=this,{children:l,isButtonGroup:d}=fa(Zn(vr(this)),t,o);return(e=this.onRender)===null||e===void 0||e.call(this),c("div",{onFocusin:n,onFocusout:r,ref:"selfElRef",class:[`${o}-radio-group`,this.rtlEnabled&&`${o}-radio-group--rtl`,this.themeClass,d&&`${o}-radio-group--button-group`],style:this.cssVars},l)}}),Wo=40,qo=40;function go(e){if(e.type==="selection")return e.width===void 0?Wo:Ot(e.width);if(e.type==="expand")return e.width===void 0?qo:Ot(e.width);if(!("children"in e))return typeof e.width=="string"?Ot(e.width):e.width}function ba(e){var t,o;if(e.type==="selection")return Ne((t=e.width)!==null&&t!==void 0?t:Wo);if(e.type==="expand")return Ne((o=e.width)!==null&&o!==void 0?o:qo);if(!("children"in e))return Ne(e.width)}function Ee(e){return e.type==="selection"?"__n_selection__":e.type==="expand"?"__n_expand__":e.key}function xo(e){return e&&(typeof e=="object"?Object.assign({},e):e)}function ma(e){return e==="ascend"?1:e==="descend"?-1:0}function va(e,t,o){return o!==void 0&&(e=Math.min(e,typeof o=="number"?o:parseFloat(o))),t!==void 0&&(e=Math.max(e,typeof t=="number"?t:parseFloat(t))),e}function ga(e,t){if(t!==void 0)return{width:t,minWidth:t,maxWidth:t};const o=ba(e),{minWidth:n,maxWidth:r}=e;return{width:o,minWidth:Ne(n)||o,maxWidth:Ne(r)}}function xa(e,t,o){return typeof o=="function"?o(e,t):o||""}function At(e){return e.filterOptionValues!==void 0||e.filterOptionValue===void 0&&e.defaultFilterOptionValues!==void 0}function Lt(e){return"children"in e?!1:!!e.sorter}function Go(e){return"children"in e&&e.children.length?!1:!!e.resizable}function yo(e){return"children"in e?!1:!!e.filter&&(!!e.filterOptions||!!e.renderFilterMenu)}function Co(e){if(e){if(e==="descend")return"ascend"}else return"descend";return!1}function ya(e,t){return e.sorter===void 0?null:t===null||t.columnKey!==e.key?{columnKey:e.key,sorter:e.sorter,order:Co(!1)}:Object.assign(Object.assign({},t),{order:Co(t.order)})}function Xo(e,t){return t.find(o=>o.columnKey===e.key&&o.order)!==void 0}const Ca=ne({name:"DataTableFilterMenu",props:{column:{type:Object,required:!0},radioGroupName:{type:String,required:!0},multiple:{type:Boolean,required:!0},value:{type:[Array,String,Number],default:null},options:{type:Array,required:!0},onConfirm:{type:Function,required:!0},onClear:{type:Function,required:!0},onChange:{type:Function,required:!0}},setup(e){const{mergedClsPrefixRef:t,mergedThemeRef:o,localeRef:n}=Te(je),r=L(e.value),l=S(()=>{const{value:f}=r;return Array.isArray(f)?f:null}),d=S(()=>{const{value:f}=r;return At(e.column)?Array.isArray(f)&&f.length&&f[0]||null:Array.isArray(f)?null:f});function a(f){e.onChange(f)}function i(f){e.multiple&&Array.isArray(f)?r.value=f:At(e.column)&&!Array.isArray(f)?r.value=[f]:r.value=f}function s(){a(r.value),e.onConfirm()}function v(){e.multiple||At(e.column)?a([]):a(null),e.onClear()}return{mergedClsPrefix:t,mergedTheme:o,locale:n,checkboxGroupValue:l,radioGroupValue:d,handleChange:i,handleConfirmClick:s,handleClearClick:v}},render(){const{mergedTheme:e,locale:t,mergedClsPrefix:o}=this;return c("div",{class:`${o}-data-table-filter-menu`},c(Bo,null,{default:()=>{const{checkboxGroupValue:n,handleChange:r}=this;return this.multiple?c(Pr,{value:n,class:`${o}-data-table-filter-menu__group`,onUpdateValue:r},{default:()=>this.options.map(l=>c(Gt,{key:l.value,theme:e.peers.Checkbox,themeOverrides:e.peerOverrides.Checkbox,value:l.value},{default:()=>l.label}))}):c(pa,{name:this.radioGroupName,class:`${o}-data-table-filter-menu__group`,value:this.radioGroupValue,onUpdateValue:this.handleChange},{default:()=>this.options.map(l=>c(Vo,{key:l.value,value:l.value,theme:e.peers.Radio,themeOverrides:e.peerOverrides.Radio},{default:()=>l.label}))})}}),c("div",{class:`${o}-data-table-filter-menu__action`},c(no,{size:"tiny",theme:e.peers.Button,themeOverrides:e.peerOverrides.Button,onClick:this.handleClearClick},{default:()=>t.clear}),c(no,{theme:e.peers.Button,themeOverrides:e.peerOverrides.Button,type:"primary",size:"tiny",onClick:this.handleConfirmClick},{default:()=>t.confirm})))}});function ka(e,t,o){const n=Object.assign({},e);return n[t]=o,n}const wa=ne({name:"DataTableFilterButton",props:{column:{type:Object,required:!0},options:{type:Array,default:()=>[]}},setup(e){const{mergedComponentPropsRef:t}=Be(),{mergedThemeRef:o,mergedClsPrefixRef:n,mergedFilterStateRef:r,filterMenuCssVarsRef:l,paginationBehaviorOnFilterRef:d,doUpdatePage:a,doUpdateFilters:i}=Te(je),s=L(!1),v=r,f=S(()=>e.column.filterMultiple!==!1),p=S(()=>{const g=v.value[e.column.key];if(g===void 0){const{value:P}=f;return P?[]:null}return g}),u=S(()=>{const{value:g}=p;return Array.isArray(g)?g.length>0:g!==null}),h=S(()=>{var g,P;return((P=(g=t==null?void 0:t.value)===null||g===void 0?void 0:g.DataTable)===null||P===void 0?void 0:P.renderFilter)||e.column.renderFilter});function m(g){const P=ka(v.value,e.column.key,g);i(P,e.column),d.value==="first"&&a(1)}function b(){s.value=!1}function x(){s.value=!1}return{mergedTheme:o,mergedClsPrefix:n,active:u,showPopover:s,mergedRenderFilter:h,filterMultiple:f,mergedFilterValue:p,filterMenuCssVars:l,handleFilterChange:m,handleFilterMenuConfirm:x,handleFilterMenuCancel:b}},render(){const{mergedTheme:e,mergedClsPrefix:t,handleFilterMenuCancel:o}=this;return c(zt,{show:this.showPopover,onUpdateShow:n=>this.showPopover=n,trigger:"click",theme:e.peers.Popover,themeOverrides:e.peerOverrides.Popover,placement:"bottom",style:{padding:0}},{trigger:()=>{const{mergedRenderFilter:n}=this;if(n)return c(la,{"data-data-table-filter":!0,render:n,active:this.active,show:this.showPopover});const{renderFilterIcon:r}=this.column;return c("div",{"data-data-table-filter":!0,class:[`${t}-data-table-filter`,{[`${t}-data-table-filter--active`]:this.active,[`${t}-data-table-filter--show`]:this.showPopover}]},r?r({active:this.active,show:this.showPopover}):c(qe,{clsPrefix:t},{default:()=>c(yr,null)}))},default:()=>{const{renderFilterMenu:n}=this.column;return n?n({hide:o}):c(Ca,{style:this.filterMenuCssVars,radioGroupName:String(this.column.key),multiple:this.filterMultiple,value:this.mergedFilterValue,options:this.options,column:this.column,onChange:this.handleFilterChange,onClear:this.handleFilterMenuCancel,onConfirm:this.handleFilterMenuConfirm})}})}}),Ra=ne({name:"ColumnResizeButton",props:{onResizeStart:Function,onResize:Function,onResizeEnd:Function},setup(e){const{mergedClsPrefixRef:t}=Te(je),o=L(!1);let n=0;function r(i){return i.clientX}function l(i){var s;const v=o.value;n=r(i),o.value=!0,v||(Nt("mousemove",window,d),Nt("mouseup",window,a),(s=e.onResizeStart)===null||s===void 0||s.call(e))}function d(i){var s;(s=e.onResize)===null||s===void 0||s.call(e,r(i)-n)}function a(){var i;o.value=!1,(i=e.onResizeEnd)===null||i===void 0||i.call(e),vt("mousemove",window,d),vt("mouseup",window,a)}return wn(()=>{vt("mousemove",window,d),vt("mouseup",window,a)}),{mergedClsPrefix:t,active:o,handleMousedown:l}},render(){const{mergedClsPrefix:e}=this;return c("span",{"data-data-table-resizable":!0,class:[`${e}-data-table-resize-button`,this.active&&`${e}-data-table-resize-button--active`],onMousedown:this.handleMousedown})}}),Zo="_n_all__",Yo="_n_none__";function Sa(e,t,o,n){return e?r=>{for(const l of e)switch(r){case Zo:o(!0);return;case Yo:n(!0);return;default:if(typeof l=="object"&&l.key===r){l.onSelect(t.value);return}}}:()=>{}}function za(e,t){return e?e.map(o=>{switch(o){case"all":return{label:t.checkTableAll,key:Zo};case"none":return{label:t.uncheckTableAll,key:Yo};default:return o}}):[]}const Pa=ne({name:"DataTableSelectionMenu",props:{clsPrefix:{type:String,required:!0}},setup(e){const{props:t,localeRef:o,checkOptionsRef:n,rawPaginatedDataRef:r,doCheckAll:l,doUncheckAll:d}=Te(je),a=S(()=>Sa(n.value,r,l,d)),i=S(()=>za(n.value,o.value));return()=>{var s,v,f,p;const{clsPrefix:u}=e;return c(br,{theme:(v=(s=t.theme)===null||s===void 0?void 0:s.peers)===null||v===void 0?void 0:v.Dropdown,themeOverrides:(p=(f=t.themeOverrides)===null||f===void 0?void 0:f.peers)===null||p===void 0?void 0:p.Dropdown,options:i.value,onSelect:a.value},{default:()=>c(qe,{clsPrefix:u,class:`${u}-data-table-check-extra`},{default:()=>c(Yn,null)})})}}});function Dt(e){return typeof e.title=="function"?e.title(e):e.title}const Jo=ne({name:"DataTableHeader",props:{discrete:{type:Boolean,default:!0}},setup(){const{mergedClsPrefixRef:e,scrollXRef:t,fixedColumnLeftMapRef:o,fixedColumnRightMapRef:n,mergedCurrentPageRef:r,allRowsCheckedRef:l,someRowsCheckedRef:d,rowsRef:a,colsRef:i,mergedThemeRef:s,checkOptionsRef:v,mergedSortStateRef:f,componentId:p,scrollPartRef:u,mergedTableLayoutRef:h,headerCheckboxDisabledRef:m,onUnstableColumnResize:b,doUpdateResizableWidth:x,handleTableHeaderScroll:g,deriveNextSorter:P,doUncheckAll:j,doCheckAll:F}=Te(je),w=L({});function _(H){const V=w.value[H];return V==null?void 0:V.getBoundingClientRect().width}function I(){l.value?j():F()}function z(H,V){if(jt(H,"dataTableFilter")||jt(H,"dataTableResizable")||!Lt(V))return;const W=f.value.find(ue=>ue.columnKey===V.key)||null,te=ya(V,W);P(te)}function C(){u.value="head"}function K(){u.value="body"}const Z=new Map;function Y(H){Z.set(H.key,_(H.key))}function X(H,V){const W=Z.get(H.key);if(W===void 0)return;const te=W+V,ue=va(te,H.minWidth,H.maxWidth);b(te,ue,H,_),x(H,ue)}return{cellElsRef:w,componentId:p,mergedSortState:f,mergedClsPrefix:e,scrollX:t,fixedColumnLeftMap:o,fixedColumnRightMap:n,currentPage:r,allRowsChecked:l,someRowsChecked:d,rows:a,cols:i,mergedTheme:s,checkOptions:v,mergedTableLayout:h,headerCheckboxDisabled:m,handleMouseenter:C,handleMouseleave:K,handleCheckboxUpdateChecked:I,handleColHeaderClick:z,handleTableHeaderScroll:g,handleColumnResizeStart:Y,handleColumnResize:X}},render(){const{cellElsRef:e,mergedClsPrefix:t,fixedColumnLeftMap:o,fixedColumnRightMap:n,currentPage:r,allRowsChecked:l,someRowsChecked:d,rows:a,cols:i,mergedTheme:s,checkOptions:v,componentId:f,discrete:p,mergedTableLayout:u,headerCheckboxDisabled:h,mergedSortState:m,handleColHeaderClick:b,handleCheckboxUpdateChecked:x,handleColumnResizeStart:g,handleColumnResize:P}=this,j=c("thead",{class:`${t}-data-table-thead`,"data-n-id":f},a.map(z=>c("tr",{class:`${t}-data-table-tr`},z.map(({column:C,colSpan:K,rowSpan:Z,isLast:Y})=>{var X,H;const V=Ee(C),{ellipsis:W}=C,te=()=>C.type==="selection"?C.multiple!==!1?c(ze,null,c(Gt,{key:r,privateInsideTable:!0,checked:l,indeterminate:d,disabled:h,onUpdateChecked:x}),v?c(Pa,{clsPrefix:t}):null):null:c(ze,null,c("div",{class:`${t}-data-table-th__title-wrapper`},c("div",{class:`${t}-data-table-th__title`},W===!0||W&&!W.tooltip?c("div",{class:`${t}-data-table-th__ellipsis`},Dt(C)):W&&typeof W=="object"?c(Ko,Object.assign({},W,{theme:s.peers.Ellipsis,themeOverrides:s.peerOverrides.Ellipsis}),{default:()=>Dt(C)}):Dt(C)),Lt(C)?c(ia,{column:C}):null),yo(C)?c(wa,{column:C,options:C.filterOptions}):null,Go(C)?c(Ra,{onResizeStart:()=>{g(C)},onResize:M=>{P(C,M)}}):null),ue=V in o,y=V in n;return c("th",{ref:M=>e[V]=M,key:V,style:{textAlign:C.titleAlign||C.align,left:pt((X=o[V])===null||X===void 0?void 0:X.start),right:pt((H=n[V])===null||H===void 0?void 0:H.start)},colspan:K,rowspan:Z,"data-col-key":V,class:[`${t}-data-table-th`,(ue||y)&&`${t}-data-table-th--fixed-${ue?"left":"right"}`,{[`${t}-data-table-th--hover`]:Xo(C,m),[`${t}-data-table-th--filterable`]:yo(C),[`${t}-data-table-th--sortable`]:Lt(C),[`${t}-data-table-th--selection`]:C.type==="selection",[`${t}-data-table-th--last`]:Y},C.className],onClick:C.type!=="selection"&&C.type!=="expand"&&!("children"in C)?M=>{b(M,C)}:void 0},te())}))));if(!p)return j;const{handleTableHeaderScroll:F,handleMouseenter:w,handleMouseleave:_,scrollX:I}=this;return c("div",{class:`${t}-data-table-base-table-header`,onScroll:F,onMouseenter:w,onMouseleave:_},c("table",{ref:"body",class:`${t}-data-table-table`,style:{minWidth:Ne(I),tableLayout:u}},c("colgroup",null,i.map(z=>c("col",{key:z.key,style:z.style}))),j))}}),Fa=ne({name:"DataTableCell",props:{clsPrefix:{type:String,required:!0},row:{type:Object,required:!0},index:{type:Number,required:!0},column:{type:Object,required:!0},isSummary:Boolean,mergedTheme:{type:Object,required:!0},renderCell:Function},render(){const{isSummary:e,column:t,row:o,renderCell:n}=this;let r;const{render:l,key:d,ellipsis:a}=t;if(l&&!e?r=l(o,this.index):e?r=o[d].value:r=n?n(ao(o,d),o,t):ao(o,d),a)if(typeof a=="object"){const{mergedTheme:i}=this;return c(Ko,Object.assign({},a,{theme:i.peers.Ellipsis,themeOverrides:i.peerOverrides.Ellipsis}),{default:()=>r})}else return c("span",{class:`${this.clsPrefix}-data-table-td__ellipsis`},r);return r}}),ko=ne({name:"DataTableExpandTrigger",props:{clsPrefix:{type:String,required:!0},expanded:Boolean,loading:Boolean,onClick:{type:Function,required:!0},renderExpandIcon:{type:Function}},render(){const{clsPrefix:e}=this;return c("div",{class:[`${e}-data-table-expand-trigger`,this.expanded&&`${e}-data-table-expand-trigger--expanded`],onClick:this.onClick},c(Mo,null,{default:()=>this.loading?c(Oo,{key:"loading",clsPrefix:this.clsPrefix,radius:85,strokeWidth:15,scale:.88}):this.renderExpandIcon?this.renderExpandIcon({expanded:this.expanded}):c(qe,{clsPrefix:e,key:"base-icon"},{default:()=>c(mr,null)})}))}}),_a=ne({name:"DataTableBodyCheckbox",props:{rowKey:{type:[String,Number],required:!0},disabled:{type:Boolean,required:!0},onUpdateChecked:{type:Function,required:!0}},setup(e){const{mergedCheckedRowKeySetRef:t,mergedInderminateRowKeySetRef:o}=Te(je);return()=>{const{rowKey:n}=e;return c(Gt,{privateInsideTable:!0,disabled:e.disabled,indeterminate:o.value.has(n),checked:t.value.has(n),onUpdateChecked:e.onUpdateChecked})}}}),Ma=ne({name:"DataTableBodyRadio",props:{rowKey:{type:[String,Number],required:!0},disabled:{type:Boolean,required:!0},onUpdateChecked:{type:Function,required:!0}},setup(e){const{mergedCheckedRowKeySetRef:t,componentId:o}=Te(je);return()=>{const{rowKey:n}=e;return c(Vo,{name:o,disabled:e.disabled,checked:t.value.has(n),onUpdateChecked:e.onUpdateChecked})}}});function $a(e,t){const o=[];function n(r,l){r.forEach(d=>{d.children&&t.has(d.key)?(o.push({tmNode:d,striped:!1,key:d.key,index:l}),n(d.children,l)):o.push({key:d.key,tmNode:d,striped:!1,index:l})})}return e.forEach(r=>{o.push(r);const{children:l}=r.tmNode;l&&t.has(r.key)&&n(l,r.index)}),o}const Ta=ne({props:{clsPrefix:{type:String,required:!0},id:{type:String,required:!0},cols:{type:Array,required:!0},onMouseenter:Function,onMouseleave:Function},render(){const{clsPrefix:e,id:t,cols:o,onMouseenter:n,onMouseleave:r}=this;return c("table",{style:{tableLayout:"fixed"},class:`${e}-data-table-table`,onMouseenter:n,onMouseleave:r},c("colgroup",null,o.map(l=>c("col",{key:l.key,style:l.style}))),c("tbody",{"data-n-id":t,class:`${e}-data-table-tbody`},this.$slots))}}),Ba=ne({name:"DataTableBody",props:{onResize:Function,showHeader:Boolean,flexHeight:Boolean,bodyStyle:Object},setup(e){const{slots:t,bodyWidthRef:o,mergedExpandedRowKeysRef:n,mergedClsPrefixRef:r,mergedThemeRef:l,scrollXRef:d,colsRef:a,paginatedDataRef:i,rawPaginatedDataRef:s,fixedColumnLeftMapRef:v,fixedColumnRightMapRef:f,mergedCurrentPageRef:p,rowClassNameRef:u,leftActiveFixedColKeyRef:h,leftActiveFixedChildrenColKeysRef:m,rightActiveFixedColKeyRef:b,rightActiveFixedChildrenColKeysRef:x,renderExpandRef:g,hoverKeyRef:P,summaryRef:j,mergedSortStateRef:F,virtualScrollRef:w,componentId:_,scrollPartRef:I,mergedTableLayoutRef:z,childTriggerColIndexRef:C,indentRef:K,rowPropsRef:Z,maxHeightRef:Y,stripedRef:X,loadingRef:H,onLoadRef:V,loadingKeySetRef:W,expandableRef:te,stickyExpandedRowsRef:ue,renderExpandIconRef:y,summaryPlacementRef:M,treeMateRef:A,scrollbarPropsRef:T,setHeaderScrollLeft:J,doUpdateExpandedRowKeys:ee,handleTableBodyScroll:he,doCheck:pe,doUncheck:le,renderCell:de}=Te(je),k=L(null),E=L(null),Pe=L(null),ke=Ge(()=>i.value.length===0),q=Ge(()=>e.showHeader||!ke.value),ae=Ge(()=>e.showHeader||ke.value);let Ae="";const we=S(()=>new Set(n.value));function xe($){var G;return(G=A.value.getNode($))===null||G===void 0?void 0:G.rawNode}function tt($,G,B){const N=xe($.key);if(!N){io("data-table",`fail to get row data with key ${$.key}`);return}if(B){const ce=i.value.findIndex(Re=>Re.key===Ae);if(ce!==-1){const Re=i.value.findIndex(We=>We.key===$.key),Oe=Math.min(ce,Re),Fe=Math.max(ce,Re),Ve=[];i.value.slice(Oe,Fe+1).forEach(We=>{We.disabled||Ve.push(We.key)}),G?pe(Ve,!1,N):le(Ve,N),Ae=$.key;return}}G?pe($.key,!1,N):le($.key,N),Ae=$.key}function ot($){const G=xe($.key);if(!G){io("data-table",`fail to get row data with key ${$.key}`);return}pe($.key,!0,G)}function Me(){if(!q.value){const{value:G}=Pe;return G||null}if(w.value)return Je();const{value:$}=k;return $?$.containerRef:null}function $e($,G){var B;if(W.value.has($))return;const{value:N}=n,ce=N.indexOf($),Re=Array.from(N);~ce?(Re.splice(ce,1),ee(Re)):G&&!G.isLeaf&&!G.shallowLoaded?(W.value.add($),(B=V.value)===null||B===void 0||B.call(V,G.rawNode).then(()=>{const{value:Oe}=n,Fe=Array.from(Oe);~Fe.indexOf($)||Fe.push($),ee(Fe)}).finally(()=>{W.value.delete($)})):(Re.push($),ee(Re))}function Ye(){P.value=null}function nt(){I.value="body"}function Je(){const{value:$}=E;return $==null?void 0:$.listElRef}function lt(){const{value:$}=E;return $==null?void 0:$.itemsElRef}function Ke($){var G;he($),(G=k.value)===null||G===void 0||G.sync()}function ge($){var G;const{onResize:B}=e;B&&B($),(G=k.value)===null||G===void 0||G.sync()}const Xe={getScrollContainer:Me,scrollTo($,G){var B,N;w.value?(B=E.value)===null||B===void 0||B.scrollTo($,G):(N=k.value)===null||N===void 0||N.scrollTo($,G)}},He=U([({props:$})=>{const G=N=>N===null?null:U(`[data-n-id="${$.componentId}"] [data-col-key="${N}"]::after`,{boxShadow:"var(--n-box-shadow-after)"}),B=N=>N===null?null:U(`[data-n-id="${$.componentId}"] [data-col-key="${N}"]::before`,{boxShadow:"var(--n-box-shadow-before)"});return U([G($.leftActiveFixedColKey),B($.rightActiveFixedColKey),$.leftActiveFixedChildrenColKeys.map(N=>G(N)),$.rightActiveFixedChildrenColKeys.map(N=>B(N))])}]);let Le=!1;return ht(()=>{const{value:$}=h,{value:G}=m,{value:B}=b,{value:N}=x;if(!Le&&$===null&&B===null)return;const ce={leftActiveFixedColKey:$,leftActiveFixedChildrenColKeys:G,rightActiveFixedColKey:B,rightActiveFixedChildrenColKeys:N,componentId:_};He.mount({id:`n-${_}`,force:!0,props:ce,anchorMetaName:or}),Le=!0}),Rn(()=>{He.unmount({id:`n-${_}`})}),Object.assign({bodyWidth:o,summaryPlacement:M,dataTableSlots:t,componentId:_,scrollbarInstRef:k,virtualListRef:E,emptyElRef:Pe,summary:j,mergedClsPrefix:r,mergedTheme:l,scrollX:d,cols:a,loading:H,bodyShowHeaderOnly:ae,shouldDisplaySomeTablePart:q,empty:ke,paginatedDataAndInfo:S(()=>{const{value:$}=X;let G=!1;return{data:i.value.map($?(N,ce)=>(N.isLeaf||(G=!0),{tmNode:N,key:N.key,striped:ce%2===1,index:ce}):(N,ce)=>(N.isLeaf||(G=!0),{tmNode:N,key:N.key,striped:!1,index:ce})),hasChildren:G}}),rawPaginatedData:s,fixedColumnLeftMap:v,fixedColumnRightMap:f,currentPage:p,rowClassName:u,renderExpand:g,mergedExpandedRowKeySet:we,hoverKey:P,mergedSortState:F,virtualScroll:w,mergedTableLayout:z,childTriggerColIndex:C,indent:K,rowProps:Z,maxHeight:Y,loadingKeySet:W,expandable:te,stickyExpandedRows:ue,renderExpandIcon:y,scrollbarProps:T,setHeaderScrollLeft:J,handleMouseenterTable:nt,handleVirtualListScroll:Ke,handleVirtualListResize:ge,handleMouseleaveTable:Ye,virtualListContainer:Je,virtualListContent:lt,handleTableBodyScroll:he,handleCheckboxUpdateChecked:tt,handleRadioUpdateChecked:ot,handleUpdateExpanded:$e,renderCell:de},Xe)},render(){const{mergedTheme:e,scrollX:t,mergedClsPrefix:o,virtualScroll:n,maxHeight:r,mergedTableLayout:l,flexHeight:d,loadingKeySet:a,onResize:i,setHeaderScrollLeft:s}=this,v=t!==void 0||r!==void 0||d,f=!v&&l==="auto",p=t!==void 0||f,u={minWidth:Ne(t)||"100%"};t&&(u.width="100%");const h=c(Bo,Object.assign({},this.scrollbarProps,{ref:"scrollbarInstRef",scrollable:v||f,class:`${o}-data-table-base-table-body`,style:this.bodyStyle,theme:e.peers.Scrollbar,themeOverrides:e.peerOverrides.Scrollbar,contentStyle:u,container:n?this.virtualListContainer:void 0,content:n?this.virtualListContent:void 0,horizontalRailStyle:{zIndex:3},verticalRailStyle:{zIndex:3},xScrollable:p,onScroll:n?void 0:this.handleTableBodyScroll,internalOnUpdateScrollLeft:s,onResize:i}),{default:()=>{const m={},b={},{cols:x,paginatedDataAndInfo:g,mergedTheme:P,fixedColumnLeftMap:j,fixedColumnRightMap:F,currentPage:w,rowClassName:_,mergedSortState:I,mergedExpandedRowKeySet:z,stickyExpandedRows:C,componentId:K,childTriggerColIndex:Z,expandable:Y,rowProps:X,handleMouseenterTable:H,handleMouseleaveTable:V,renderExpand:W,summary:te,handleCheckboxUpdateChecked:ue,handleRadioUpdateChecked:y,handleUpdateExpanded:M}=this,{length:A}=x;let T;const{data:J,hasChildren:ee}=g,he=ee?$a(J,z):J;if(te){const q=te(this.rawPaginatedData);if(Array.isArray(q)){const ae=q.map((Ae,we)=>({isSummaryRow:!0,key:`__n_summary__${we}`,tmNode:{rawNode:Ae,disabled:!0},index:-1}));T=this.summaryPlacement==="top"?[...ae,...he]:[...he,...ae]}else{const ae={isSummaryRow:!0,key:"__n_summary__",tmNode:{rawNode:q,disabled:!0},index:-1};T=this.summaryPlacement==="top"?[ae,...he]:[...he,ae]}}else T=he;const pe=ee?{width:pt(this.indent)}:void 0,le=[];T.forEach(q=>{W&&z.has(q.key)&&(!Y||Y(q.tmNode.rawNode))?le.push(q,{isExpandedRow:!0,key:`${q.key}-expand`,tmNode:q.tmNode,index:q.index}):le.push(q)});const{length:de}=le,k={};J.forEach(({tmNode:q},ae)=>{k[ae]=q.key});const E=C?this.bodyWidth:null,Pe=E===null?void 0:`${E}px`,ke=(q,ae,Ae)=>{const{index:we}=q;if("isExpandedRow"in q){const{tmNode:{key:Ke,rawNode:ge}}=q;return c("tr",{class:`${o}-data-table-tr`,key:`${Ke}__expand`},c("td",{class:[`${o}-data-table-td`,`${o}-data-table-td--last-col`,ae+1===de&&`${o}-data-table-td--last-row`],colspan:A},C?c("div",{class:`${o}-data-table-expand`,style:{width:Pe}},W(ge,we)):W(ge,we)))}const xe="isSummaryRow"in q,tt=!xe&&q.striped,{tmNode:ot,key:Me}=q,{rawNode:$e}=ot,Ye=z.has(Me),nt=X?X($e,we):void 0,Je=typeof _=="string"?_:xa($e,we,_);return c("tr",Object.assign({onMouseenter:()=>{this.hoverKey=Me},key:Me,class:[`${o}-data-table-tr`,xe&&`${o}-data-table-tr--summary`,tt&&`${o}-data-table-tr--striped`,Je]},nt),x.map((Ke,ge)=>{var Xe,He,Le,$,G;if(ae in m){const Se=m[ae],_e=Se.indexOf(ge);if(~_e)return Se.splice(_e,1),null}const{column:B}=Ke,N=Ee(Ke),{rowSpan:ce,colSpan:Re}=B,Oe=xe?((Xe=q.tmNode.rawNode[N])===null||Xe===void 0?void 0:Xe.colSpan)||1:Re?Re($e,we):1,Fe=xe?((He=q.tmNode.rawNode[N])===null||He===void 0?void 0:He.rowSpan)||1:ce?ce($e,we):1,Ve=ge+Oe===A,We=ae+Fe===de,Qe=Fe>1;if(Qe&&(b[ae]={[ge]:[]}),Oe>1||Qe)for(let Se=ae;Se<ae+Fe;++Se){Qe&&b[ae][ge].push(k[Se]);for(let _e=ge;_e<ge+Oe;++_e)Se===ae&&_e===ge||(Se in m?m[Se].push(_e):m[Se]=[_e])}const st=Qe?this.hoverKey:null,{cellProps:rt}=B,De=rt==null?void 0:rt($e,we);return c("td",Object.assign({},De,{key:N,style:[{textAlign:B.align||void 0,left:pt((Le=j[N])===null||Le===void 0?void 0:Le.start),right:pt(($=F[N])===null||$===void 0?void 0:$.start)},(De==null?void 0:De.style)||""],colspan:Oe,rowspan:Ae?void 0:Fe,"data-col-key":N,class:[`${o}-data-table-td`,B.className,De==null?void 0:De.class,xe&&`${o}-data-table-td--summary`,(st!==null&&b[ae][ge].includes(st)||Xo(B,I))&&`${o}-data-table-td--hover`,B.fixed&&`${o}-data-table-td--fixed-${B.fixed}`,B.align&&`${o}-data-table-td--${B.align}-align`,B.type==="selection"&&`${o}-data-table-td--selection`,B.type==="expand"&&`${o}-data-table-td--expand`,Ve&&`${o}-data-table-td--last-col`,We&&`${o}-data-table-td--last-row`]}),ee&&ge===Z?[er(xe?0:q.tmNode.level,c("div",{class:`${o}-data-table-indent`,style:pe})),xe||q.tmNode.isLeaf?c("div",{class:`${o}-data-table-expand-placeholder`}):c(ko,{class:`${o}-data-table-expand-trigger`,clsPrefix:o,expanded:Ye,renderExpandIcon:this.renderExpandIcon,loading:a.has(q.key),onClick:()=>{M(Me,q.tmNode)}})]:null,B.type==="selection"?xe?null:B.multiple===!1?c(Ma,{key:w,rowKey:Me,disabled:q.tmNode.disabled,onUpdateChecked:()=>{y(q.tmNode)}}):c(_a,{key:w,rowKey:Me,disabled:q.tmNode.disabled,onUpdateChecked:(Se,_e)=>{ue(q.tmNode,Se,_e.shiftKey)}}):B.type==="expand"?xe?null:!B.expandable||!((G=B.expandable)===null||G===void 0)&&G.call(B,$e)?c(ko,{clsPrefix:o,expanded:Ye,renderExpandIcon:this.renderExpandIcon,onClick:()=>{M(Me,null)}}):null:c(Fa,{clsPrefix:o,index:we,row:$e,column:B,isSummary:xe,mergedTheme:P,renderCell:this.renderCell}))}))};return n?c(Jn,{ref:"virtualListRef",items:le,itemSize:28,visibleItemsTag:Ta,visibleItemsProps:{clsPrefix:o,id:K,cols:x,onMouseenter:H,onMouseleave:V},showScrollbar:!1,onResize:this.handleVirtualListResize,onScroll:this.handleVirtualListScroll,itemsStyle:u,itemResizable:!0},{default:({item:q,index:ae})=>ke(q,ae,!0)}):c("table",{class:`${o}-data-table-table`,onMouseleave:V,onMouseenter:H,style:{tableLayout:this.mergedTableLayout}},c("colgroup",null,x.map(q=>c("col",{key:q.key,style:q.style}))),this.showHeader?c(Jo,{discrete:!1}):null,this.empty?null:c("tbody",{"data-n-id":K,class:`${o}-data-table-tbody`},le.map((q,ae)=>ke(q,ae,!1))))}});if(this.empty){const m=()=>c("div",{class:[`${o}-data-table-empty`,this.loading&&`${o}-data-table-empty--hide`],style:this.bodyStyle,ref:"emptyElRef"},qt(this.dataTableSlots.empty,()=>[c(tr,{theme:this.mergedTheme.peers.Empty,themeOverrides:this.mergedTheme.peerOverrides.Empty})]));return this.shouldDisplaySomeTablePart?c(ze,null,h,m()):c(Qn,{onResize:this.onResize},{default:m})}return h}}),Oa=ne({setup(){const{mergedClsPrefixRef:e,rightFixedColumnsRef:t,leftFixedColumnsRef:o,bodyWidthRef:n,maxHeightRef:r,minHeightRef:l,flexHeightRef:d,syncScrollState:a}=Te(je),i=L(null),s=L(null),v=L(null),f=L(!(o.value.length||t.value.length)),p=S(()=>({maxHeight:Ne(r.value),minHeight:Ne(l.value)}));function u(x){n.value=x.contentRect.width,a(),f.value||(f.value=!0)}function h(){const{value:x}=i;return x?x.$el:null}function m(){const{value:x}=s;return x?x.getScrollContainer():null}const b={getBodyElement:m,getHeaderElement:h,scrollTo(x,g){var P;(P=s.value)===null||P===void 0||P.scrollTo(x,g)}};return ht(()=>{const{value:x}=v;if(!x)return;const g=`${e.value}-data-table-base-table--transition-disabled`;f.value?setTimeout(()=>{x.classList.remove(g)},0):x.classList.add(g)}),Object.assign({maxHeight:r,mergedClsPrefix:e,selfElRef:v,headerInstRef:i,bodyInstRef:s,bodyStyle:p,flexHeight:d,handleBodyResize:u},b)},render(){const{mergedClsPrefix:e,maxHeight:t,flexHeight:o}=this,n=t===void 0&&!o;return c("div",{class:`${e}-data-table-base-table`,ref:"selfElRef"},n?null:c(Jo,{ref:"headerInstRef"}),c(Ba,{ref:"bodyInstRef",bodyStyle:this.bodyStyle,showHeader:n,flexHeight:o,onResize:this.handleBodyResize}))}});function Aa(e,t){const{paginatedDataRef:o,treeMateRef:n,selectionColumnRef:r}=t,l=L(e.defaultCheckedRowKeys),d=S(()=>{var F;const{checkedRowKeys:w}=e,_=w===void 0?l.value:w;return((F=r.value)===null||F===void 0?void 0:F.multiple)===!1?{checkedKeys:_.slice(0,1),indeterminateKeys:[]}:n.value.getCheckedKeys(_,{cascade:e.cascade,allowNotLoaded:e.allowCheckingNotLoaded})}),a=S(()=>d.value.checkedKeys),i=S(()=>d.value.indeterminateKeys),s=S(()=>new Set(a.value)),v=S(()=>new Set(i.value)),f=S(()=>{const{value:F}=s;return o.value.reduce((w,_)=>{const{key:I,disabled:z}=_;return w+(!z&&F.has(I)?1:0)},0)}),p=S(()=>o.value.filter(F=>F.disabled).length),u=S(()=>{const{length:F}=o.value,{value:w}=v;return f.value>0&&f.value<F-p.value||o.value.some(_=>w.has(_.key))}),h=S(()=>{const{length:F}=o.value;return f.value!==0&&f.value===F-p.value}),m=S(()=>o.value.length===0);function b(F,w,_){const{"onUpdate:checkedRowKeys":I,onUpdateCheckedRowKeys:z,onCheckedRowKeysChange:C}=e,K=[],{value:{getNode:Z}}=n;F.forEach(Y=>{var X;const H=(X=Z(Y))===null||X===void 0?void 0:X.rawNode;K.push(H)}),I&&D(I,F,K,{row:w,action:_}),z&&D(z,F,K,{row:w,action:_}),C&&D(C,F,K,{row:w,action:_}),l.value=F}function x(F,w=!1,_){if(!e.loading){if(w){b(Array.isArray(F)?F.slice(0,1):[F],_,"check");return}b(n.value.check(F,a.value,{cascade:e.cascade,allowNotLoaded:e.allowCheckingNotLoaded}).checkedKeys,_,"check")}}function g(F,w){e.loading||b(n.value.uncheck(F,a.value,{cascade:e.cascade,allowNotLoaded:e.allowCheckingNotLoaded}).checkedKeys,w,"uncheck")}function P(F=!1){const{value:w}=r;if(!w||e.loading)return;const _=[];(F?n.value.treeNodes:o.value).forEach(I=>{I.disabled||_.push(I.key)}),b(n.value.check(_,a.value,{cascade:!0,allowNotLoaded:e.allowCheckingNotLoaded}).checkedKeys,void 0,"checkAll")}function j(F=!1){const{value:w}=r;if(!w||e.loading)return;const _=[];(F?n.value.treeNodes:o.value).forEach(I=>{I.disabled||_.push(I.key)}),b(n.value.uncheck(_,a.value,{cascade:!0,allowNotLoaded:e.allowCheckingNotLoaded}).checkedKeys,void 0,"uncheckAll")}return{mergedCheckedRowKeySetRef:s,mergedCheckedRowKeysRef:a,mergedInderminateRowKeySetRef:v,someRowsCheckedRef:u,allRowsCheckedRef:h,headerCheckboxDisabledRef:m,doUpdateCheckedRowKeys:b,doCheckAll:P,doUncheckAll:j,doCheck:x,doUncheck:g}}function gt(e){return typeof e=="object"&&typeof e.multiple=="number"?e.multiple:!1}function La(e,t){return t&&(e===void 0||e==="default"||typeof e=="object"&&e.compare==="default")?Da(t):typeof e=="function"?e:e&&typeof e=="object"&&e.compare&&e.compare!=="default"?e.compare:!1}function Da(e){return(t,o)=>{const n=t[e],r=o[e];return typeof n=="number"&&typeof r=="number"?n-r:typeof n=="string"&&typeof r=="string"?n.localeCompare(r):0}}function Ea(e,{dataRelatedColsRef:t,filteredDataRef:o}){const n=[];t.value.forEach(u=>{var h;u.sorter!==void 0&&p(n,{columnKey:u.key,sorter:u.sorter,order:(h=u.defaultSortOrder)!==null&&h!==void 0?h:!1})});const r=L(n),l=S(()=>{const u=t.value.filter(b=>b.type!=="selection"&&b.sorter!==void 0&&(b.sortOrder==="ascend"||b.sortOrder==="descend"||b.sortOrder===!1)),h=u.filter(b=>b.sortOrder!==!1);if(h.length)return h.map(b=>({columnKey:b.key,order:b.sortOrder,sorter:b.sorter}));if(u.length)return[];const{value:m}=r;return Array.isArray(m)?m:m?[m]:[]}),d=S(()=>{const u=l.value.slice().sort((h,m)=>{const b=gt(h.sorter)||0;return(gt(m.sorter)||0)-b});return u.length?o.value.slice().sort((m,b)=>{let x=0;return u.some(g=>{const{columnKey:P,sorter:j,order:F}=g,w=La(j,P);return w&&F&&(x=w(m.rawNode,b.rawNode),x!==0)?(x=x*ma(F),!0):!1}),x}):o.value});function a(u){let h=l.value.slice();return u&&gt(u.sorter)!==!1?(h=h.filter(m=>gt(m.sorter)!==!1),p(h,u),h):u||null}function i(u){const h=a(u);s(h)}function s(u){const{"onUpdate:sorter":h,onUpdateSorter:m,onSorterChange:b}=e;h&&D(h,u),m&&D(m,u),b&&D(b,u),r.value=u}function v(u,h="ascend"){if(!u)f();else{const m=t.value.find(x=>x.type!=="selection"&&x.type!=="expand"&&x.key===u);if(!(m!=null&&m.sorter))return;const b=m.sorter;i({columnKey:u,sorter:b,order:h})}}function f(){s(null)}function p(u,h){const m=u.findIndex(b=>(h==null?void 0:h.columnKey)&&b.columnKey===h.columnKey);m!==void 0&&m>=0?u[m]=h:u.push(h)}return{clearSorter:f,sort:v,sortedDataRef:d,mergedSortStateRef:l,deriveNextSorter:i}}function Ua(e,{dataRelatedColsRef:t}){const o=S(()=>{const y=M=>{for(let A=0;A<M.length;++A){const T=M[A];if("children"in T)return y(T.children);if(T.type==="selection")return T}return null};return y(e.columns)}),n=S(()=>{const{childrenKey:y}=e;return $o(e.data,{ignoreEmptyChildren:!0,getKey:e.rowKey,getChildren:M=>M[y],getDisabled:M=>{var A,T;return!!(!((T=(A=o.value)===null||A===void 0?void 0:A.disabled)===null||T===void 0)&&T.call(A,M))}})}),r=Ge(()=>{const{columns:y}=e,{length:M}=y;let A=null;for(let T=0;T<M;++T){const J=y[T];if(!J.type&&A===null&&(A=T),"tree"in J&&J.tree)return T}return A||0}),l=L({}),d=L(1),a=L(10),i=S(()=>{const y=t.value.filter(T=>T.filterOptionValues!==void 0||T.filterOptionValue!==void 0),M={};return y.forEach(T=>{var J;T.type==="selection"||T.type==="expand"||(T.filterOptionValues===void 0?M[T.key]=(J=T.filterOptionValue)!==null&&J!==void 0?J:null:M[T.key]=T.filterOptionValues)}),Object.assign(xo(l.value),M)}),s=S(()=>{const y=i.value,{columns:M}=e;function A(ee){return(he,pe)=>!!~String(pe[ee]).indexOf(String(he))}const{value:{treeNodes:T}}=n,J=[];return M.forEach(ee=>{ee.type==="selection"||ee.type==="expand"||"children"in ee||J.push([ee.key,ee])}),T?T.filter(ee=>{const{rawNode:he}=ee;for(const[pe,le]of J){let de=y[pe];if(de==null||(Array.isArray(de)||(de=[de]),!de.length))continue;const k=le.filter==="default"?A(pe):le.filter;if(le&&typeof k=="function")if(le.filterMode==="and"){if(de.some(E=>!k(E,he)))return!1}else{if(de.some(E=>k(E,he)))continue;return!1}}return!0}):[]}),{sortedDataRef:v,deriveNextSorter:f,mergedSortStateRef:p,sort:u,clearSorter:h}=Ea(e,{dataRelatedColsRef:t,filteredDataRef:s});t.value.forEach(y=>{var M;if(y.filter){const A=y.defaultFilterOptionValues;y.filterMultiple?l.value[y.key]=A||[]:A!==void 0?l.value[y.key]=A===null?[]:A:l.value[y.key]=(M=y.defaultFilterOptionValue)!==null&&M!==void 0?M:null}});const m=S(()=>{const{pagination:y}=e;if(y!==!1)return y.page}),b=S(()=>{const{pagination:y}=e;if(y!==!1)return y.pageSize}),x=Ze(m,d),g=Ze(b,a),P=Ge(()=>{const y=x.value;return e.remote?y:Math.max(1,Math.min(Math.ceil(s.value.length/g.value),y))}),j=S(()=>{const{pagination:y}=e;if(y){const{pageCount:M}=y;if(M!==void 0)return M}}),F=S(()=>{if(e.remote)return n.value.treeNodes;if(!e.pagination)return v.value;const y=g.value,M=(P.value-1)*y;return v.value.slice(M,M+y)}),w=S(()=>F.value.map(y=>y.rawNode));function _(y){const{pagination:M}=e;if(M){const{onChange:A,"onUpdate:page":T,onUpdatePage:J}=M;A&&D(A,y),J&&D(J,y),T&&D(T,y),K(y)}}function I(y){const{pagination:M}=e;if(M){const{onPageSizeChange:A,"onUpdate:pageSize":T,onUpdatePageSize:J}=M;A&&D(A,y),J&&D(J,y),T&&D(T,y),Z(y)}}const z=S(()=>{if(e.remote){const{pagination:y}=e;if(y){const{itemCount:M}=y;if(M!==void 0)return M}return}return s.value.length}),C=S(()=>Object.assign(Object.assign({},e.pagination),{onChange:void 0,onUpdatePage:void 0,onUpdatePageSize:void 0,onPageSizeChange:void 0,"onUpdate:page":_,"onUpdate:pageSize":I,page:P.value,pageSize:g.value,pageCount:z.value===void 0?j.value:void 0,itemCount:z.value}));function K(y){const{"onUpdate:page":M,onPageChange:A,onUpdatePage:T}=e;T&&D(T,y),M&&D(M,y),A&&D(A,y),d.value=y}function Z(y){const{"onUpdate:pageSize":M,onPageSizeChange:A,onUpdatePageSize:T}=e;A&&D(A,y),T&&D(T,y),M&&D(M,y),a.value=y}function Y(y,M){const{onUpdateFilters:A,"onUpdate:filters":T,onFiltersChange:J}=e;A&&D(A,y,M),T&&D(T,y,M),J&&D(J,y,M),l.value=y}function X(y,M,A,T){var J;(J=e.onUnstableColumnResize)===null||J===void 0||J.call(e,y,M,A,T)}function H(y){K(y)}function V(){W()}function W(){te({})}function te(y){ue(y)}function ue(y){y?y&&(l.value=xo(y)):l.value={}}return{treeMateRef:n,mergedCurrentPageRef:P,mergedPaginationRef:C,paginatedDataRef:F,rawPaginatedDataRef:w,mergedFilterStateRef:i,mergedSortStateRef:p,hoverKeyRef:L(null),selectionColumnRef:o,childTriggerColIndexRef:r,doUpdateFilters:Y,deriveNextSorter:f,doUpdatePageSize:Z,doUpdatePage:K,onUnstableColumnResize:X,filter:ue,filters:te,clearFilter:V,clearFilters:W,clearSorter:h,page:H,sort:u}}function Ia(e,{mainTableInstRef:t,mergedCurrentPageRef:o,bodyWidthRef:n,scrollPartRef:r}){let l=0;const d=L(null),a=L([]),i=L(null),s=L([]),v=S(()=>Ne(e.scrollX)),f=S(()=>e.columns.filter(z=>z.fixed==="left")),p=S(()=>e.columns.filter(z=>z.fixed==="right")),u=S(()=>{const z={};let C=0;function K(Z){Z.forEach(Y=>{const X={start:C,end:0};z[Ee(Y)]=X,"children"in Y?(K(Y.children),X.end=C):(C+=go(Y)||0,X.end=C)})}return K(f.value),z}),h=S(()=>{const z={};let C=0;function K(Z){for(let Y=Z.length-1;Y>=0;--Y){const X=Z[Y],H={start:C,end:0};z[Ee(X)]=H,"children"in X?(K(X.children),H.end=C):(C+=go(X)||0,H.end=C)}}return K(p.value),z});function m(){var z,C;const{value:K}=f;let Z=0;const{value:Y}=u;let X=null;for(let H=0;H<K.length;++H){const V=Ee(K[H]);if(l>(((z=Y[V])===null||z===void 0?void 0:z.start)||0)-Z)X=V,Z=((C=Y[V])===null||C===void 0?void 0:C.end)||0;else break}d.value=X}function b(){a.value=[];let z=e.columns.find(C=>Ee(C)===d.value);for(;z&&"children"in z;){const C=z.children.length;if(C===0)break;const K=z.children[C-1];a.value.push(Ee(K)),z=K}}function x(){var z,C;const{value:K}=p,Z=Number(e.scrollX),{value:Y}=n;if(Y===null)return;let X=0,H=null;const{value:V}=h;for(let W=K.length-1;W>=0;--W){const te=Ee(K[W]);if(Math.round(l+(((z=V[te])===null||z===void 0?void 0:z.start)||0)+Y-X)<Z)H=te,X=((C=V[te])===null||C===void 0?void 0:C.end)||0;else break}i.value=H}function g(){s.value=[];let z=e.columns.find(C=>Ee(C)===i.value);for(;z&&"children"in z&&z.children.length;){const C=z.children[0];s.value.push(Ee(C)),z=C}}function P(){const z=t.value?t.value.getHeaderElement():null,C=t.value?t.value.getBodyElement():null;return{header:z,body:C}}function j(){const{body:z}=P();z&&(z.scrollTop=0)}function F(){r.value==="head"&&lo(_)}function w(z){var C;(C=e.onScroll)===null||C===void 0||C.call(e,z),r.value==="body"&&lo(_)}function _(){const{header:z,body:C}=P();if(!C)return;const{value:K}=n;if(K===null)return;const{value:Z}=r;if(e.maxHeight||e.flexHeight){if(!z)return;Z==="head"?(l=z.scrollLeft,C.scrollLeft=l):(l=C.scrollLeft,z.scrollLeft=l)}else l=C.scrollLeft;m(),b(),x(),g()}function I(z){const{header:C}=P();C&&(C.scrollLeft=z,_())}return Ht(o,()=>{j()}),{styleScrollXRef:v,fixedColumnLeftMapRef:u,fixedColumnRightMapRef:h,leftFixedColumnsRef:f,rightFixedColumnsRef:p,leftActiveFixedColKeyRef:d,leftActiveFixedChildrenColKeysRef:a,rightActiveFixedColKeyRef:i,rightActiveFixedChildrenColKeysRef:s,syncScrollState:_,handleTableBodyScroll:w,handleTableHeaderScroll:F,setHeaderScrollLeft:I}}function Na(){const e=L({});function t(r){return e.value[r]}function o(r,l){Go(r)&&"key"in r&&(e.value[r.key]=l)}function n(){e.value={}}return{getResizableWidth:t,doUpdateResizableWidth:o,clearResizableWidth:n}}function ja(e,t){const o=[],n=[],r=[],l=new WeakMap;let d=-1,a=0,i=!1;function s(p,u){u>d&&(o[u]=[],d=u);for(const h of p)if("children"in h)s(h.children,u+1);else{const m="key"in h?h.key:void 0;n.push({key:Ee(h),style:ga(h,m!==void 0?Ne(t(m)):void 0),column:h}),a+=1,i||(i=!!h.ellipsis),r.push(h)}}s(e,0);let v=0;function f(p,u){let h=0;p.forEach((m,b)=>{var x;if("children"in m){const g=v,P={column:m,colSpan:0,rowSpan:1,isLast:!1};f(m.children,u+1),m.children.forEach(j=>{var F,w;P.colSpan+=(w=(F=l.get(j))===null||F===void 0?void 0:F.colSpan)!==null&&w!==void 0?w:0}),g+P.colSpan===a&&(P.isLast=!0),l.set(m,P),o[u].push(P)}else{if(v<h){v+=1;return}let g=1;"titleColSpan"in m&&(g=(x=m.titleColSpan)!==null&&x!==void 0?x:1),g>1&&(h=v+g);const P=v+g===a,j={column:m,colSpan:g,rowSpan:d-u+1,isLast:P};l.set(m,j),o[u].push(j),v+=1}})}return f(e,0),{hasEllipsis:i,rows:o,cols:n,dataRelatedCols:r}}function Ka(e,t){const o=S(()=>ja(e.columns,t));return{rowsRef:S(()=>o.value.rows),colsRef:S(()=>o.value.cols),hasEllipsisRef:S(()=>o.value.hasEllipsis),dataRelatedColsRef:S(()=>o.value.dataRelatedCols)}}function Ha(e,t){const o=Ge(()=>{for(const s of e.columns)if(s.type==="expand")return s.renderExpand}),n=Ge(()=>{let s;for(const v of e.columns)if(v.type==="expand"){s=v.expandable;break}return s}),r=L(e.defaultExpandAll?o!=null&&o.value?(()=>{const s=[];return t.value.treeNodes.forEach(v=>{var f;!((f=n.value)===null||f===void 0)&&f.call(n,v.rawNode)&&s.push(v.key)}),s})():t.value.getNonLeafKeys():e.defaultExpandedRowKeys),l=ie(e,"expandedRowKeys"),d=ie(e,"stickyExpandedRows"),a=Ze(l,r);function i(s){const{onUpdateExpandedRowKeys:v,"onUpdate:expandedRowKeys":f}=e;v&&D(v,s),f&&D(f,s),r.value=s}return{stickyExpandedRowsRef:d,mergedExpandedRowKeysRef:a,renderExpandRef:o,expandableRef:n,doUpdateExpandedRowKeys:i}}const wo=Wa(),Va=U([R("data-table",`
 width: 100%;
 font-size: var(--n-font-size);
 display: flex;
 flex-direction: column;
 position: relative;
 --n-merged-th-color: var(--n-th-color);
 --n-merged-td-color: var(--n-td-color);
 --n-merged-border-color: var(--n-border-color);
 --n-merged-th-color-hover: var(--n-th-color-hover);
 --n-merged-td-color-hover: var(--n-td-color-hover);
 --n-merged-td-color-striped: var(--n-td-color-striped);
 `,[R("data-table-wrapper",`
 flex-grow: 1;
 display: flex;
 flex-direction: column;
 `),O("flex-height",[U(">",[R("data-table-wrapper",[U(">",[R("data-table-base-table",`
 display: flex;
 flex-direction: column;
 flex-grow: 1;
 `,[U(">",[R("data-table-base-table-body","flex-basis: 0;",[U("&:last-child","flex-grow: 1;")])])])])])])]),U(">",[R("data-table-loading-wrapper",`
 color: var(--n-loading-color);
 font-size: var(--n-loading-size);
 position: absolute;
 left: 50%;
 top: 50%;
 transform: translateX(-50%) translateY(-50%);
 transition: color .3s var(--n-bezier);
 display: flex;
 align-items: center;
 justify-content: center;
 `,[nr({originalTransform:"translateX(-50%) translateY(-50%)"})])]),R("data-table-expand-placeholder",`
 margin-right: 8px;
 display: inline-block;
 width: 16px;
 height: 1px;
 `),R("data-table-indent",`
 display: inline-block;
 height: 1px;
 `),R("data-table-expand-trigger",`
 display: inline-flex;
 margin-right: 8px;
 cursor: pointer;
 font-size: 16px;
 vertical-align: -0.2em;
 position: relative;
 width: 16px;
 height: 16px;
 color: var(--n-td-text-color);
 transition: color .3s var(--n-bezier);
 `,[O("expanded",[R("icon","transform: rotate(90deg);",[dt({originalTransform:"rotate(90deg)"})]),R("base-icon","transform: rotate(90deg);",[dt({originalTransform:"rotate(90deg)"})])]),R("base-loading",`
 color: var(--n-loading-color);
 transition: color .3s var(--n-bezier);
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `,[dt()]),R("icon",`
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `,[dt()]),R("base-icon",`
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 `,[dt()])]),R("data-table-thead",`
 transition: background-color .3s var(--n-bezier);
 background-color: var(--n-merged-th-color);
 `),R("data-table-tr",`
 box-sizing: border-box;
 background-clip: padding-box;
 transition: background-color .3s var(--n-bezier);
 `,[R("data-table-expand",`
 position: sticky;
 left: 0;
 overflow: hidden;
 margin: calc(var(--n-th-padding) * -1);
 padding: var(--n-th-padding);
 box-sizing: border-box;
 `),O("striped","background-color: var(--n-merged-td-color-striped);",[R("data-table-td","background-color: var(--n-merged-td-color-striped);")]),at("summary",[U("&:hover","background-color: var(--n-merged-td-color-hover);",[U(">",[R("data-table-td","background-color: var(--n-merged-td-color-hover);")])])])]),R("data-table-th",`
 padding: var(--n-th-padding);
 position: relative;
 text-align: start;
 box-sizing: border-box;
 background-color: var(--n-merged-th-color);
 border-color: var(--n-merged-border-color);
 border-bottom: 1px solid var(--n-merged-border-color);
 color: var(--n-th-text-color);
 transition:
 border-color .3s var(--n-bezier),
 color .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 font-weight: var(--n-th-font-weight);
 `,[O("filterable",`
 padding-right: 36px;
 `,[O("sortable",`
 padding-right: calc(var(--n-th-padding) + 36px);
 `)]),wo,O("selection",`
 padding: 0;
 text-align: center;
 line-height: 0;
 z-index: 3;
 `),oe("title-wrapper",`
 display: flex;
 align-items: center;
 flex-wrap: nowrap;
 max-width: 100%;
 `,[oe("title",`
 flex: 1;
 min-width: 0;
 `)]),oe("ellipsis",`
 display: inline-block;
 vertical-align: bottom;
 text-overflow: ellipsis;
 overflow: hidden;
 white-space: nowrap;
 max-width: 100%;
 `),O("hover",`
 background-color: var(--n-merged-th-color-hover);
 `),O("sortable",`
 cursor: pointer;
 `,[oe("ellipsis",`
 max-width: calc(100% - 18px);
 `),U("&:hover",`
 background-color: var(--n-merged-th-color-hover);
 `)]),R("data-table-sorter",`
 height: var(--n-sorter-size);
 width: var(--n-sorter-size);
 margin-left: 4px;
 position: relative;
 display: inline-flex;
 align-items: center;
 justify-content: center;
 vertical-align: -0.2em;
 color: var(--n-th-icon-color);
 transition: color .3s var(--n-bezier);
 `,[R("base-icon","transition: transform .3s var(--n-bezier)"),O("desc",[R("base-icon",`
 transform: rotate(0deg);
 `)]),O("asc",[R("base-icon",`
 transform: rotate(-180deg);
 `)]),O("asc, desc",`
 color: var(--n-th-icon-color-active);
 `)]),R("data-table-resize-button",`
 width: var(--n-resizable-container-size);
 position: absolute;
 top: 0;
 right: calc(var(--n-resizable-container-size) / 2);
 bottom: 0;
 cursor: col-resize;
 user-select: none;
 `,[U("&::after",`
 width: var(--n-resizable-size);
 height: 50%;
 position: absolute;
 top: 50%;
 left: calc(var(--n-resizable-container-size) / 2);
 bottom: 0;
 background-color: var(--n-merged-border-color);
 transform: translateY(-50%);
 transition: background-color .3s var(--n-bezier);
 z-index: 1;
 content: '';
 `),O("active",[U("&::after",` 
 background-color: var(--n-th-icon-color-active);
 `)]),U("&:hover::after",`
 background-color: var(--n-th-icon-color-active);
 `)]),R("data-table-filter",`
 position: absolute;
 z-index: auto;
 right: 0;
 width: 36px;
 top: 0;
 bottom: 0;
 cursor: pointer;
 display: flex;
 justify-content: center;
 align-items: center;
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 font-size: var(--n-filter-size);
 color: var(--n-th-icon-color);
 `,[U("&:hover",`
 background-color: var(--n-th-button-color-hover);
 `),O("show",`
 background-color: var(--n-th-button-color-hover);
 `),O("active",`
 background-color: var(--n-th-button-color-hover);
 color: var(--n-th-icon-color-active);
 `)])]),R("data-table-td",`
 padding: var(--n-td-padding);
 text-align: start;
 box-sizing: border-box;
 border: none;
 background-color: var(--n-merged-td-color);
 color: var(--n-td-text-color);
 border-bottom: 1px solid var(--n-merged-border-color);
 transition:
 box-shadow .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 border-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 `,[O("expand",[R("data-table-expand-trigger",`
 margin-right: 0;
 `)]),O("last-row",`
 border-bottom: 0 solid var(--n-merged-border-color);
 `,[U("&::after",`
 bottom: 0 !important;
 `),U("&::before",`
 bottom: 0 !important;
 `)]),O("summary",`
 background-color: var(--n-merged-th-color);
 `),O("hover",`
 background-color: var(--n-merged-td-color-hover);
 `),oe("ellipsis",`
 display: inline-block;
 text-overflow: ellipsis;
 overflow: hidden;
 white-space: nowrap;
 max-width: 100%;
 vertical-align: bottom;
 `),O("selection, expand",`
 text-align: center;
 padding: 0;
 line-height: 0;
 `),wo]),R("data-table-empty",`
 box-sizing: border-box;
 padding: var(--n-empty-padding);
 flex-grow: 1;
 flex-shrink: 0;
 opacity: 1;
 display: flex;
 align-items: center;
 justify-content: center;
 transition: opacity .3s var(--n-bezier);
 `,[O("hide",`
 opacity: 0;
 `)]),oe("pagination",`
 margin: var(--n-pagination-margin);
 display: flex;
 justify-content: flex-end;
 `),R("data-table-wrapper",`
 position: relative;
 opacity: 1;
 transition: opacity .3s var(--n-bezier), border-color .3s var(--n-bezier);
 border-top-left-radius: var(--n-border-radius);
 border-top-right-radius: var(--n-border-radius);
 line-height: var(--n-line-height);
 `),O("loading",[R("data-table-wrapper",`
 opacity: var(--n-opacity-loading);
 pointer-events: none;
 `)]),O("single-column",[R("data-table-td",`
 border-bottom: 0 solid var(--n-merged-border-color);
 `,[U("&::after, &::before",`
 bottom: 0 !important;
 `)])]),at("single-line",[R("data-table-th",`
 border-right: 1px solid var(--n-merged-border-color);
 `,[O("last",`
 border-right: 0 solid var(--n-merged-border-color);
 `)]),R("data-table-td",`
 border-right: 1px solid var(--n-merged-border-color);
 `,[O("last-col",`
 border-right: 0 solid var(--n-merged-border-color);
 `)])]),O("bordered",[R("data-table-wrapper",`
 border: 1px solid var(--n-merged-border-color);
 border-bottom-left-radius: var(--n-border-radius);
 border-bottom-right-radius: var(--n-border-radius);
 overflow: hidden;
 `)]),R("data-table-base-table",[O("transition-disabled",[R("data-table-th",[U("&::after, &::before","transition: none;")]),R("data-table-td",[U("&::after, &::before","transition: none;")])])]),O("bottom-bordered",[R("data-table-td",[O("last-row",`
 border-bottom: 1px solid var(--n-merged-border-color);
 `)])]),R("data-table-table",`
 font-variant-numeric: tabular-nums;
 width: 100%;
 word-break: break-word;
 transition: background-color .3s var(--n-bezier);
 border-collapse: separate;
 border-spacing: 0;
 background-color: var(--n-merged-td-color);
 `),R("data-table-base-table-header",`
 border-top-left-radius: calc(var(--n-border-radius) - 1px);
 border-top-right-radius: calc(var(--n-border-radius) - 1px);
 z-index: 3;
 overflow: scroll;
 flex-shrink: 0;
 transition: border-color .3s var(--n-bezier);
 scrollbar-width: none;
 `,[U("&::-webkit-scrollbar",`
 width: 0;
 height: 0;
 `)]),R("data-table-check-extra",`
 transition: color .3s var(--n-bezier);
 color: var(--n-th-icon-color);
 position: absolute;
 font-size: 14px;
 right: -4px;
 top: 50%;
 transform: translateY(-50%);
 z-index: 1;
 `)]),R("data-table-filter-menu",[R("scrollbar",`
 max-height: 240px;
 `),oe("group",`
 display: flex;
 flex-direction: column;
 padding: 12px 12px 0 12px;
 `,[R("checkbox",`
 margin-bottom: 12px;
 margin-right: 0;
 `),R("radio",`
 margin-bottom: 12px;
 margin-right: 0;
 `)]),oe("action",`
 padding: var(--n-action-padding);
 display: flex;
 flex-wrap: nowrap;
 justify-content: space-evenly;
 border-top: 1px solid var(--n-action-divider-color);
 `,[R("button",[U("&:not(:last-child)",`
 margin: var(--n-action-button-margin);
 `),U("&:last-child",`
 margin-right: 0;
 `)])]),R("divider",`
 margin: 0 !important;
 `)]),Po(R("data-table",`
 --n-merged-th-color: var(--n-th-color-modal);
 --n-merged-td-color: var(--n-td-color-modal);
 --n-merged-border-color: var(--n-border-color-modal);
 --n-merged-th-color-hover: var(--n-th-color-hover-modal);
 --n-merged-td-color-hover: var(--n-td-color-hover-modal);
 --n-merged-td-color-striped: var(--n-td-color-striped-modal);
 `)),Fo(R("data-table",`
 --n-merged-th-color: var(--n-th-color-popover);
 --n-merged-td-color: var(--n-td-color-popover);
 --n-merged-border-color: var(--n-border-color-popover);
 --n-merged-th-color-hover: var(--n-th-color-hover-popover);
 --n-merged-td-color-hover: var(--n-td-color-hover-popover);
 --n-merged-td-color-striped: var(--n-td-color-striped-popover);
 `))]);function Wa(){return[O("fixed-left",`
 left: 0;
 position: sticky;
 z-index: 2;
 `,[U("&::after",`
 pointer-events: none;
 content: "";
 width: 36px;
 display: inline-block;
 position: absolute;
 top: 0;
 bottom: -1px;
 transition: box-shadow .2s var(--n-bezier);
 right: -36px;
 `)]),O("fixed-right",`
 right: 0;
 position: sticky;
 z-index: 1;
 `,[U("&::before",`
 pointer-events: none;
 content: "";
 width: 36px;
 display: inline-block;
 position: absolute;
 top: 0;
 bottom: -1px;
 transition: box-shadow .2s var(--n-bezier);
 left: -36px;
 `)])]}const qa=ne({name:"DataTable",alias:["AdvancedTable"],props:aa,setup(e,{slots:t}){const{mergedBorderedRef:o,mergedClsPrefixRef:n,inlineThemeDisabled:r}=Be(e),l=S(()=>{const{bottomBordered:B}=e;return o.value?!1:B!==void 0?B:!0}),d=Ce("DataTable","-data-table",Va,Qr,e,n),a=L(null),i=L("body");So(()=>{i.value="body"});const s=L(null),{getResizableWidth:v,clearResizableWidth:f,doUpdateResizableWidth:p}=Na(),{rowsRef:u,colsRef:h,dataRelatedColsRef:m,hasEllipsisRef:b}=Ka(e,v),{treeMateRef:x,mergedCurrentPageRef:g,paginatedDataRef:P,rawPaginatedDataRef:j,selectionColumnRef:F,hoverKeyRef:w,mergedPaginationRef:_,mergedFilterStateRef:I,mergedSortStateRef:z,childTriggerColIndexRef:C,doUpdatePage:K,doUpdateFilters:Z,onUnstableColumnResize:Y,deriveNextSorter:X,filter:H,filters:V,clearFilter:W,clearFilters:te,clearSorter:ue,page:y,sort:M}=Ua(e,{dataRelatedColsRef:m}),{doCheckAll:A,doUncheckAll:T,doCheck:J,doUncheck:ee,headerCheckboxDisabledRef:he,someRowsCheckedRef:pe,allRowsCheckedRef:le,mergedCheckedRowKeySetRef:de,mergedInderminateRowKeySetRef:k}=Aa(e,{selectionColumnRef:F,treeMateRef:x,paginatedDataRef:P}),{stickyExpandedRowsRef:E,mergedExpandedRowKeysRef:Pe,renderExpandRef:ke,expandableRef:q,doUpdateExpandedRowKeys:ae}=Ha(e,x),{handleTableBodyScroll:Ae,handleTableHeaderScroll:we,syncScrollState:xe,setHeaderScrollLeft:tt,leftActiveFixedColKeyRef:ot,leftActiveFixedChildrenColKeysRef:Me,rightActiveFixedColKeyRef:$e,rightActiveFixedChildrenColKeysRef:Ye,leftFixedColumnsRef:nt,rightFixedColumnsRef:Je,fixedColumnLeftMapRef:lt,fixedColumnRightMapRef:Ke}=Ia(e,{scrollPartRef:i,bodyWidthRef:a,mainTableInstRef:s,mergedCurrentPageRef:g}),{localeRef:ge}=To("DataTable"),Xe=S(()=>e.virtualScroll||e.flexHeight||e.maxHeight!==void 0||b.value?"fixed":e.tableLayout);Ct(je,{props:e,treeMateRef:x,renderExpandIconRef:ie(e,"renderExpandIcon"),loadingKeySetRef:L(new Set),slots:t,indentRef:ie(e,"indent"),childTriggerColIndexRef:C,bodyWidthRef:a,componentId:_o(),hoverKeyRef:w,mergedClsPrefixRef:n,mergedThemeRef:d,scrollXRef:S(()=>e.scrollX),rowsRef:u,colsRef:h,paginatedDataRef:P,leftActiveFixedColKeyRef:ot,leftActiveFixedChildrenColKeysRef:Me,rightActiveFixedColKeyRef:$e,rightActiveFixedChildrenColKeysRef:Ye,leftFixedColumnsRef:nt,rightFixedColumnsRef:Je,fixedColumnLeftMapRef:lt,fixedColumnRightMapRef:Ke,mergedCurrentPageRef:g,someRowsCheckedRef:pe,allRowsCheckedRef:le,mergedSortStateRef:z,mergedFilterStateRef:I,loadingRef:ie(e,"loading"),rowClassNameRef:ie(e,"rowClassName"),mergedCheckedRowKeySetRef:de,mergedExpandedRowKeysRef:Pe,mergedInderminateRowKeySetRef:k,localeRef:ge,scrollPartRef:i,expandableRef:q,stickyExpandedRowsRef:E,rowKeyRef:ie(e,"rowKey"),renderExpandRef:ke,summaryRef:ie(e,"summary"),virtualScrollRef:ie(e,"virtualScroll"),rowPropsRef:ie(e,"rowProps"),stripedRef:ie(e,"striped"),checkOptionsRef:S(()=>{const{value:B}=F;return B==null?void 0:B.options}),rawPaginatedDataRef:j,filterMenuCssVarsRef:S(()=>{const{self:{actionDividerColor:B,actionPadding:N,actionButtonMargin:ce}}=d.value;return{"--n-action-padding":N,"--n-action-button-margin":ce,"--n-action-divider-color":B}}),onLoadRef:ie(e,"onLoad"),mergedTableLayoutRef:Xe,maxHeightRef:ie(e,"maxHeight"),minHeightRef:ie(e,"minHeight"),flexHeightRef:ie(e,"flexHeight"),headerCheckboxDisabledRef:he,paginationBehaviorOnFilterRef:ie(e,"paginationBehaviorOnFilter"),summaryPlacementRef:ie(e,"summaryPlacement"),scrollbarPropsRef:ie(e,"scrollbarProps"),syncScrollState:xe,doUpdatePage:K,doUpdateFilters:Z,getResizableWidth:v,onUnstableColumnResize:Y,clearResizableWidth:f,doUpdateResizableWidth:p,deriveNextSorter:X,doCheck:J,doUncheck:ee,doCheckAll:A,doUncheckAll:T,doUpdateExpandedRowKeys:ae,handleTableHeaderScroll:we,handleTableBodyScroll:Ae,setHeaderScrollLeft:tt,renderCell:ie(e,"renderCell")});const He={filter:H,filters:V,clearFilters:te,clearSorter:ue,page:y,sort:M,clearFilter:W,scrollTo:(B,N)=>{var ce;(ce=s.value)===null||ce===void 0||ce.scrollTo(B,N)}},Le=S(()=>{const{size:B}=e,{common:{cubicBezierEaseInOut:N},self:{borderColor:ce,tdColorHover:Re,thColor:Oe,thColorHover:Fe,tdColor:Ve,tdTextColor:We,thTextColor:Qe,thFontWeight:st,thButtonColorHover:rt,thIconColor:De,thIconColorActive:Se,filterSize:_e,borderRadius:Pt,lineHeight:Ft,tdColorModal:_t,thColorModal:Mt,borderColorModal:$t,thColorHoverModal:Tt,tdColorHoverModal:Qo,borderColorPopover:en,thColorPopover:tn,tdColorPopover:on,tdColorHoverPopover:nn,thColorHoverPopover:rn,paginationMargin:an,emptyPadding:ln,boxShadowAfter:sn,boxShadowBefore:dn,sorterSize:cn,resizableContainerSize:un,resizableSize:fn,loadingColor:hn,loadingSize:pn,opacityLoading:bn,tdColorStriped:mn,tdColorStripedModal:vn,tdColorStripedPopover:gn,[fe("fontSize",B)]:xn,[fe("thPadding",B)]:yn,[fe("tdPadding",B)]:Cn}}=d.value;return{"--n-font-size":xn,"--n-th-padding":yn,"--n-td-padding":Cn,"--n-bezier":N,"--n-border-radius":Pt,"--n-line-height":Ft,"--n-border-color":ce,"--n-border-color-modal":$t,"--n-border-color-popover":en,"--n-th-color":Oe,"--n-th-color-hover":Fe,"--n-th-color-modal":Mt,"--n-th-color-hover-modal":Tt,"--n-th-color-popover":tn,"--n-th-color-hover-popover":rn,"--n-td-color":Ve,"--n-td-color-hover":Re,"--n-td-color-modal":_t,"--n-td-color-hover-modal":Qo,"--n-td-color-popover":on,"--n-td-color-hover-popover":nn,"--n-th-text-color":Qe,"--n-td-text-color":We,"--n-th-font-weight":st,"--n-th-button-color-hover":rt,"--n-th-icon-color":De,"--n-th-icon-color-active":Se,"--n-filter-size":_e,"--n-pagination-margin":an,"--n-empty-padding":ln,"--n-box-shadow-before":dn,"--n-box-shadow-after":sn,"--n-sorter-size":cn,"--n-resizable-container-size":un,"--n-resizable-size":fn,"--n-loading-size":pn,"--n-loading-color":hn,"--n-opacity-loading":bn,"--n-td-color-striped":mn,"--n-td-color-striped-modal":vn,"--n-td-color-striped-popover":gn}}),$=r?ft("data-table",S(()=>e.size[0]),Le,e):void 0,G=S(()=>{if(!e.pagination)return!1;if(e.paginateSinglePage)return!0;const B=_.value,{pageCount:N}=B;return N!==void 0?N>1:B.itemCount&&B.pageSize&&B.itemCount>B.pageSize});return Object.assign({mainTableInstRef:s,mergedClsPrefix:n,mergedTheme:d,paginatedData:P,mergedBordered:o,mergedBottomBordered:l,mergedPagination:_,mergedShowPagination:G,cssVars:r?void 0:Le,themeClass:$==null?void 0:$.themeClass,onRender:$==null?void 0:$.onRender},He)},render(){const{mergedClsPrefix:e,themeClass:t,onRender:o,$slots:n,spinProps:r}=this;return o==null||o(),c("div",{class:[`${e}-data-table`,t,{[`${e}-data-table--bordered`]:this.mergedBordered,[`${e}-data-table--bottom-bordered`]:this.mergedBottomBordered,[`${e}-data-table--single-line`]:this.singleLine,[`${e}-data-table--single-column`]:this.singleColumn,[`${e}-data-table--loading`]:this.loading,[`${e}-data-table--flex-height`]:this.flexHeight}],style:this.cssVars},c("div",{class:`${e}-data-table-wrapper`},c(Oa,{ref:"mainTableInstRef"})),this.mergedShowPagination?c("div",{class:`${e}-data-table__pagination`},c(jr,Object.assign({theme:this.mergedTheme.peers.Pagination,themeOverrides:this.mergedTheme.peerOverrides.Pagination,disabled:this.loading},this.mergedPagination))):null,c(Sn,{name:"fade-in-scale-up-transition"},{default:()=>this.loading?c("div",{class:`${e}-data-table-loading-wrapper`},qt(n.loading,()=>[c(Oo,Object.assign({clsPrefix:e,strokeWidth:20},r))])):null}))}}),Ga={viewBox:"0 0 24 24",width:"1.2em",height:"1.2em"},Xa=se("path",{fill:"currentColor",d:"M8.59 16.58L13.17 12L8.59 7.41L10 6l6 6l-6 6l-1.41-1.42Z"},null,-1),Za=[Xa];function Ya(e,t){return Q(),re("svg",Ga,Za)}const Ja={name:"mdi-chevron-right",render:Ya},Qa={viewBox:"0 0 24 24",width:"1.2em",height:"1.2em"},ei=se("path",{fill:"currentColor",d:"M15.41 16.58L10.83 12l4.58-4.59L14 6l-6 6l6 6l1.41-1.42Z"},null,-1),ti=[ei];function oi(e,t){return Q(),re("svg",Qa,ti)}const ni={name:"mdi-chevron-left",render:oi},ri={class:"relative"},ai=ne({__name:"ChartHeaderScroller",props:{itemClass:{},steps:{default:3}},setup(e){const t=e,o=L(),{width:n}=rr(o),{x:r}=ar(o),l=L(0),d=()=>{l.value=o.value.scrollLeft},a=S(()=>{var f;return l.value+n.value!==((f=o.value)==null?void 0:f.scrollWidth)}),i=L(0);Ht(()=>t.itemClass,()=>{yt(()=>{const f=document.querySelector("."+t.itemClass);i.value=(f==null?void 0:f.clientWidth)??0})},{immediate:!0});const s=()=>{r.value+=i.value*t.steps},v=()=>{r.value-=i.value*t.steps};return(f,p)=>{const u=ni,h=Ja;return Q(),re("header",ri,[l.value!==0?(Q(),re("button",{key:0,class:"absolute z-10 top-1/3 opacity-40 hover:opacity-100 transition bg-primary h-10 w-10 text-white text-2xl flex items-center justify-center rounded-full left-0",onClick:p[0]||(p[0]=m=>v())},[be(u)])):Ut("",!0),se("div",{ref_key:"presenterRef",ref:o,onScroll:d,class:"flex w-full relative snap-x overflow-hidden thumb-transparent h-32 px-10 scroll-smooth text-body-1/50 space-x-2 divide-x divide-dashed divide-opacity-20 divide-body-1 bg-base-lvl-2 mb-2"},[xt(f.$slots,"default")],544),a.value?(Q(),re("button",{key:1,class:"absolute top-1/3 opacity-40 hover:opacity-100 transition bg-primary h-10 w-10 text-white text-2xl flex items-center justify-center rounded-full right-0",onClick:p[1]||(p[1]=m=>s())},[be(h)])):Ut("",!0)])}}});const ii={class:"w-full comparison-card"},li={class:"pb-10 rounded-lg"},si={class:"card-text"},di=["onClick"],ci={class:"period-title"},ui={class:"period-value text-sm"},fi={__name:"ChartNetworth",props:{title:{type:String},type:{type:String,default:"bar"},data:{type:Object,required:!0}},setup(e){const t=e,o=d=>ir(Dn(d),"MMMM"),n=L(),r=S(()=>[{name:"Debts",data:Object.values(t.data).map(d=>Math.abs(d.debts)),labels:Object.values(t.data).map(d=>d.month_date)},{name:"Assets",data:Object.values(t.data).map(d=>d.assets),labels:Object.values(t.data).map(d=>d.month_date)}]),l=zn({headers:Object.entries(t.data).map(([d,a])=>({label:o(a.month_date),value:[a.debts,a.assets],id:a.month_date})),options:{colors:["#7B77D1","#F37EA1"]},series:r});return(d,a)=>(Q(),re("div",ii,[se("div",li,[se("div",si,[be(ai,{"item-class":"comparison-header__item"},{default:ye(()=>[(Q(!0),re(ze,null,Ue(l.headers,i=>(Q(),re("section",{key:i.id,onClick:s=>n.value=i.id,class:"comparison-header__item select-none snap-center min-w-max px-6 justify-center w-full items-center flex-col flex previous-period cursor-pointer hover:text-body/80"},[se("h6",ci,Ie(i.label),1),(Q(!0),re(ze,null,Ue(i.value,(s,v)=>(Q(),re("span",ui,[se("div",{class:"absolute -left-4 top-2 h-2 w-2 rounded-full",style:Pn({backgroundColor:l.options.colors[v]})},null,4),be(lr),kt(" "+Ie(me(ut)(s)),1)]))),256))],8,di))),128))]),_:1}),be(En,{label:"name",type:"bar",labels:r.value[0].labels,options:l.options,series:l.series},null,8,["labels","options","series"])])])]))}},hi=sr(fi,[["__scopeId","data-v-a203431a"]]),bt={__name:"Collapse",props:{title:{type:String},titleClass:{type:String},gap:{type:Boolean,default:!0},contentClass:{type:String}},setup(e){const t=L(),{isCollapsed:o,icon:n,toggleCollapse:r}=Ao(t);return(l,d)=>(Q(),re("article",{ref_key:"incomeRef",ref:t},[se("header",{class:et(["cursor-pointer",e.titleClass]),onClick:d[0]||(d[0]=a=>me(r)())},[xt(l.$slots,"header",{icon:me(n),isCollapsed:me(o)},()=>[se("i",{class:et([me(n),"mr-2"])},null,2),xt(l.$slots,"title",{},()=>[kt(Ie(e.title),1)])])],2),me(o)?Ut("",!0):(Q(),re("section",{key:0,class:et([e.gap&&"pl-4",e.contentClass])},[xt(l.$slots,"content")],2))],512))}},Ro={__name:"TableCell",props:{col:{type:Object,required:!0},value:{type:[String,Number,Date]}},setup(e){return(t,o)=>(Q(),re("div",{class:et(e.col.class)},Ie(e.col.render?e.col.render(e.value):e.value),3))}},pi={class:"px-4 py-2 divide-y"},bi={class:"flex px-4 py-2 mt-4 bg-base"},mi={class:"mt-2"},vi={__name:"IncomeExpenses",props:{data:{type:Object}},setup(e){const t=e,o=L();Ao(o);const n=S(()=>[{field:"name",class:"font-bold text-sm"},...t.data.dateUnits.map(r=>({field:r,label:dr(r),class:"text-right text-sm",render:l=>ut(l||0)})),{field:"avg",label:"AVG",class:"text-right text-sm",render:r=>ut(r||0)},{field:"total",label:"Total",class:"text-right text-sm",render:r=>ut(r||0)}]);return(r,l)=>(Q(),re("section",pi,[se("header",bi,[(Q(!0),re(ze,null,Ue(n.value,d=>(Q(),re("div",{key:d.field,class:et(["w-full font-bold capitalize",d.class])},Ie(d.label||d.field),3))),128))]),be(bt,{title:"Income","title-class":"p-2 font-bold text-success bg-base-lvl-1 hover:bg-base","content-class":"pb-4",gap:!1},{content:ye(()=>[(Q(!0),re(ze,null,Ue(e.data.incomeCategories,d=>(Q(),re("div",{key:d.id,class:"flex space-x-2 group hover:bg-primary/5"},[(Q(!0),re(ze,null,Ue(n.value,a=>(Q(),ct(Ro,{key:a.field,class:et(["w-full px-2 py-2 transition cursor-pointer hover:font-bold hover:text-primary",{"group-hover:text-primary text-secondary pl-4":a.field=="name"}]),title:a.label,col:a,value:e.data.incomes[d.name][a.field]},null,8,["class","title","col","value"]))),128))]))),128))]),_:1}),be(bt,{title:"Expenses","title-class":"p-2 font-bold text-error bg-base-lvl-1 hover:bg-base","content-class":"pb-4",gap:!1},{content:ye(()=>[se("section",mi,[(Q(!0),re(ze,null,Ue(e.data.expenseCategories,(d,a)=>(Q(),ct(bt,{title:a,"title-class":"px-2 py-1 font-bold rounded-md text-primary bg-base-lvl-2",key:a,class:"mt-2",gap:!1},{content:ye(()=>[(Q(!0),re(ze,null,Ue(d,i=>(Q(),re("div",{key:i.id,class:"flex space-x-2 group hover:bg-primary/5"},[(Q(!0),re(ze,null,Ue(n.value,s=>(Q(),ct(Ro,{key:s.field,class:et(["w-full px-2 py-2 transition cursor-pointer hover:font-bold hover:text-primary",{"group-hover:text-primary text-secondary/80 pl-6":s.field=="name"}]),title:s.label,col:s,value:e.data.expenses[`${a}:${i.name}`][s.field]},null,8,["class","title","col","value"]))),128))]))),128))]),_:2},1032,["title"]))),128))])]),_:1})]))}},gi={viewBox:"0 0 24 24",width:"1.2em",height:"1.2em"},xi=se("path",{fill:"currentColor",d:"M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7a5 5 0 0 0-5 5a5 5 0 0 0 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1M8 13h8v-2H8v2m9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1c0 1.71-1.39 3.1-3.1 3.1h-4V17h4a5 5 0 0 0 5-5a5 5 0 0 0-5-5Z"},null,-1),yi=[xi];function Ci(e,t){return Q(),re("svg",gi,yi)}const ki={name:"mdi-link",render:Ci},wi={class:"flex items-center justify-center"},Ri={class:"relative w-[500px]"},Si={class:"absolute text-center top-1/2 left-44 text-primary font-bold"},zi={class:"space-y-1"},Pi={class:"w-full flex justify-between px-4 cursor-pointer"},Fi={class:"font-bold"},_i={class:""},Et=ne({__name:"ExpenseChartWidget",props:{data:{},componentProps:{},title:{},type:{}},emits:["selected"],setup(e,{emit:t}){const o=e,n=a=>{t("selected",a)},r=S(()=>o.data.reduce((a,i)=>{try{return fr.add(a,i.total)}catch{return 0}},0)),l=a=>a?a.split("|").map(i=>{const s=i.split(":");return{id:s[0],accountName:s[1],date:s[2],payeeName:s[3],concept:s[4],amount:s[5]}}):null,d=a=>{const i={categories:"category_id",groups:"group_id"},s=i[o.type]??i.groups,v=location.search.replace("?","&");return`/finance/lines?${s}=${a.id||a.category_id}${v}`};return(a,i)=>{const s=ki;return Q(),re("article",wi,[se("section",Ri,[be(cr,Vt({style:{background:"white",width:"100%"},series:a.data,data:a.data,onClicked:n},a.componentProps,{label:"name",value:"total",legend:!1}),null,16,["series","data"]),se("section",Si,[se("h4",null,Ie(a.title),1),se("h5",null,Ie(me(ut)(r.value)),1)])]),se("div",zi,[(Q(!0),re(ze,null,Ue(a.data,v=>(Q(),ct(me(zt),{trigger:"click"},{trigger:ye(()=>[se("p",Pi,[se("span",Fi,Ie(v.name)+": ",1),be(me(zo),{class:"ml-4 hover:underline group items-center hover:text-primary flex",href:d(v)},{default:ye(()=>[kt(Ie(me(ut)(v.total))+" ",1),be(s,{class:"ml-1 group-hover:visible invisible"})]),_:2},1032,["href"])])]),default:ye(()=>[se("div",_i,[be(me(qa),{columns:Object.keys(l(v.details)[0]).map(f=>({key:f,title:Fn(f)})),data:l(v.details)},null,8,["columns","data"])])]),_:2},1024))),256))])])}}}),Mi={class:"w-full comparison-card"},$i={__name:"YearSummary",props:{title:{type:String},hideHeader:{type:Boolean},headerTemplate:{type:String,default:"row"},type:{type:String,default:"bar"},data:{type:Object,required:!0}},setup(e){return(t,o)=>(Q(),re("div",Mi))}},Ti=se("span",null,"All categories",-1),Bi=se("span",null,"All accounts",-1),Oi={class:"relative flex flex-wrap items-center justify-center w-full bg-base-lvl-3 md:flex-nowrap md:space-x-8"},Ai={class:"flex overflow-hidden text-white border rounded-md bg-primary border-primary min-w-max"},Li=["onClick"],Di={class:"divide-y divide-base-lvl-2 bg-base-lvl-3"},Ei={class:"divide-y divide-base-lvl-2 bg-base-lvl-3"},bl={__name:"Trends",props:{user:{type:Object,required:!0},data:{type:Array,default(){return[]}},metaData:{type:Object},serverSearchOptions:{type:Object,default:()=>({})},section:{type:String}},setup(e){const t=e,{serverSearchOptions:o}=_n(t),{state:n,executeSearchWithDelay:r}=On(o,{manual:!0}),l=f=>{const p=t.data[f];t.metaData.parent_name||Bt.visit(`/trends/categories?filter[parent_id]=${p.id}`)},d=[{name:"Cashflow",link:"/trends"},{name:"Category Trends",link:"/trends/categories"},{name:"Net Worth",link:"/trends/net-worth"},{name:"Income v Expenses",link:"/trends/income-expenses"},{name:"Income vs Expenses Graph",link:"/trends/income-expenses-graph"},{name:"Year summary",link:"/trends/year-summary"}],a={groups:Et,categories:Et,netWorth:hi,incomeExpenses:vi,incomeExpensesGraph:Un,yearSummary:$i},i=S(()=>a[t.metaData.name]||Et),s={groups:{label:"Groups",value:"/trends/groups"},categories:{label:"Categories",value:"/trends/categories"},payees:{label:"Payees",value:"/trends/payees"}},v=f=>location.pathname.includes(f);return(f,p)=>(Q(),ct(Bn,{title:e.metaData.title},{header:ye(()=>[be(An,null,{actions:ye(()=>[Ti,Bi,be(me(kn),{class:"w-full h-12 border-none bg-base-lvl-1 text-body",startDate:me(n).dates.startDate,"onUpdate:startDate":p[0]||(p[0]=u=>me(n).dates.startDate=u),endDate:me(n).dates.endDate,"onUpdate:endDate":p[1]||(p[1]=u=>me(n).dates.endDate=u),onChange:p[2]||(p[2]=u=>me(r)(500)),controlsClass:"bg-transparent text-body hover:bg-base-lvl-1","next-mode":"month"},null,8,["startDate","endDate"])]),_:1})]),default:ye(()=>[be(Ln,{title:"Finance",ref:"financeTemplateRef"},{panel:ye(()=>[be(bt,{title:"Reports","title-class":"p-2 mt-6 font-bold rounded-md bg-base-lvl-3 text-body-1 bg-base-lvl-1 ","content-class":"pb-4 bg-base-lvl-3"},{content:ye(()=>[se("div",Di,[(Q(),re(ze,null,Ue(d,u=>be(me(zo),{href:u.link,key:u.name,class:"block px-2 py-4 cursor-pointer hover:bg-base-lvl-2"},{default:ye(()=>[kt(Ie(u.name),1)]),_:2},1032,["href"])),64))])]),_:1}),be(bt,{title:"Report Summary","title-class":"p-2 mt-6 font-bold rounded-md bg-base-lvl-3 text-body-1 bg-base-lvl-1 ","content-class":"pb-4 bg-base-lvl-3"},{content:ye(()=>[se("article",Ei,[be(so,{title:"Total Spending",value:5e3,subtitle:"This period"}),be(so,{title:"Average Spending",value:5e3,subtitle:"Per month"})])]),_:1})]),default:ye(()=>[be(ur,{title:"Trends preview",class:"mt-5",action:{label:"Go to Trends",iconClass:"fa fa-chevron-right"},onAction:p[3]||(p[3]=u=>me(Bt).visit("/trends"))},{afterActions:ye(()=>[se("div",Ai,[(Q(),re(ze,null,Ue(s,(u,h)=>se("button",{class:et(["px-2 py-1.5 flex items-center border border-transparent hover:bg-accent",{"bg-white text-primary border-primary hover:text-white":v(h)}]),key:h,onClick:m=>me(Bt).visit(u.value)},Ie(u.label),11,Li)),64))])]),default:ye(()=>[se("section",Oi,[(Q(),ct(Mn(i.value),Vt({style:{background:"white",width:"100%"},type:e.section,series:e.data,data:e.data,onSelected:l},e.metaData.props,{title:e.metaData.title,label:"name",value:"total",legend:!1}),null,16,["type","series","data","title"]))])]),_:1})]),_:1},512)]),_:1},8,["title"]))}};export{bl as default};

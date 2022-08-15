import{z as ie,r as $,E as Ne,H as ro,C as l,k as Go,X as mo,U as X,p as lo,q as B,T as Co,x as Te,D as oo,Z as Zo,F as Xo,w as Yo,j as Jo}from"./app.ed3e752b.js";import{n as Qo,r as en,d as O,e as H,f as j,h as me,k as on,N as io,a as $e,i as T,u as de,j as W,g as Ie,c as ao,b as I}from"./light.2fdb7969.js";import{v as nn,w as tn,x as uo,s as rn,y as so,f as xo,z as ln,e as no,N as an,g as sn,h as He,c as le,W as cn,u as ho,l as dn,n as to,V as un,a as hn,b as fn,q as fo}from"./fade-in-scale-up.cssr.07c62ce8.js";import{a as co,d as vn,b as Xe,c as gn}from"./AppLayout.3e3cea3d.js";import{u as yo,h as bn,V as pn,j as mn,g as Ye,F as Cn,b as xn,e as vo,c as yn,a as wn,m as Sn}from"./Suffix.8e8bf306.js";import{r as Pe,d as zn,p as kn,N as Pn,u as Rn,c as Fn}from"./Popover.b027aa1f.js";function On(e){switch(typeof e){case"string":return e||void 0;case"number":return String(e);default:return}}function Je(e){const t=e.filter(n=>n!==void 0);if(t.length!==0)return t.length===1?t[0]:n=>{e.forEach(a=>{a&&a(n)})}}const Se="v-hidden",Mn=nn("[v-hidden]",{display:"none!important"}),go=ie({name:"Overflow",props:{getCounter:Function,getTail:Function,updateCounter:Function,onUpdateOverflow:Function},setup(e,{slots:t}){const n=$(null),a=$(null);function i(){const{value:d}=n,{getCounter:r,getTail:m}=e;let x;if(r!==void 0?x=r():x=a.value,!d||!x)return;x.hasAttribute(Se)&&x.removeAttribute(Se);const{children:g}=d,y=d.offsetWidth,w=[],p=t.tail?m==null?void 0:m():null;let u=p?p.offsetWidth:0,S=!1;const P=d.children.length-(t.tail?1:0);for(let z=0;z<P-1;++z){if(z<0)continue;const E=g[z];if(S){E.hasAttribute(Se)||E.setAttribute(Se,"");continue}else E.hasAttribute(Se)&&E.removeAttribute(Se);const K=E.offsetWidth;if(u+=K,w[z]=K,u>y){const{updateCounter:U}=e;for(let L=z;L>=0;--L){const D=P-1-L;U!==void 0?U(D):x.textContent=`${D}`;const q=x.offsetWidth;if(u-=w[L],u+q<=y||L===0){S=!0,z=L-1,p&&(z===-1?(p.style.maxWidth=`${y-q}px`,p.style.boxSizing="border-box"):p.style.maxWidth="");break}}}}const{onUpdateOverflow:C}=e;S?C!==void 0&&C(!0):(C!==void 0&&C(!1),x.setAttribute(Se,""))}const f=Qo();return Mn.mount({id:"vueuc/overflow",head:!0,anchorMetaName:tn,ssr:f}),Ne(i),{selfRef:n,counterRef:a,sync:i}},render(){const{$slots:e}=this;return ro(this.sync),l("div",{class:"v-overflow",ref:"selfRef"},[Go(e,"default"),e.counter?e.counter():l("span",{style:{display:"inline-block"},ref:"counterRef"}),e.tail?e.tail():null])}});function wo(e,t){t&&(Ne(()=>{const{value:n}=e;n&&uo.registerHandler(n,t)}),mo(()=>{const{value:n}=e;n&&uo.unregisterHandler(n)}))}const Tn=ie({name:"Checkmark",render(){return l("svg",{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 16 16"},l("g",{fill:"none"},l("path",{d:"M14.046 3.486a.75.75 0 0 1-.032 1.06l-7.93 7.474a.85.85 0 0 1-1.188-.022l-2.68-2.72a.75.75 0 1 1 1.068-1.053l2.234 2.267l7.468-7.038a.75.75 0 0 1 1.06.032z",fill:"currentColor"})))}}),$n=en("close",l("svg",{viewBox:"0 0 12 12",version:"1.1",xmlns:"http://www.w3.org/2000/svg","aria-hidden":!0},l("g",{stroke:"none","stroke-width":"1",fill:"none","fill-rule":"evenodd"},l("g",{fill:"currentColor","fill-rule":"nonzero"},l("path",{d:"M2.08859116,2.2156945 L2.14644661,2.14644661 C2.32001296,1.97288026 2.58943736,1.95359511 2.7843055,2.08859116 L2.85355339,2.14644661 L6,5.293 L9.14644661,2.14644661 C9.34170876,1.95118446 9.65829124,1.95118446 9.85355339,2.14644661 C10.0488155,2.34170876 10.0488155,2.65829124 9.85355339,2.85355339 L6.707,6 L9.85355339,9.14644661 C10.0271197,9.32001296 10.0464049,9.58943736 9.91140884,9.7843055 L9.85355339,9.85355339 C9.67998704,10.0271197 9.41056264,10.0464049 9.2156945,9.91140884 L9.14644661,9.85355339 L6,6.707 L2.85355339,9.85355339 C2.65829124,10.0488155 2.34170876,10.0488155 2.14644661,9.85355339 C1.95118446,9.65829124 1.95118446,9.34170876 2.14644661,9.14644661 L5.293,6 L2.14644661,2.85355339 C1.97288026,2.67998704 1.95359511,2.41056264 2.08859116,2.2156945 L2.14644661,2.14644661 L2.08859116,2.2156945 Z"}))))),In=ie({name:"Empty",render(){return l("svg",{viewBox:"0 0 28 28",fill:"none",xmlns:"http://www.w3.org/2000/svg"},l("path",{d:"M26 7.5C26 11.0899 23.0899 14 19.5 14C15.9101 14 13 11.0899 13 7.5C13 3.91015 15.9101 1 19.5 1C23.0899 1 26 3.91015 26 7.5ZM16.8536 4.14645C16.6583 3.95118 16.3417 3.95118 16.1464 4.14645C15.9512 4.34171 15.9512 4.65829 16.1464 4.85355L18.7929 7.5L16.1464 10.1464C15.9512 10.3417 15.9512 10.6583 16.1464 10.8536C16.3417 11.0488 16.6583 11.0488 16.8536 10.8536L19.5 8.20711L22.1464 10.8536C22.3417 11.0488 22.6583 11.0488 22.8536 10.8536C23.0488 10.6583 23.0488 10.3417 22.8536 10.1464L20.2071 7.5L22.8536 4.85355C23.0488 4.65829 23.0488 4.34171 22.8536 4.14645C22.6583 3.95118 22.3417 3.95118 22.1464 4.14645L19.5 6.79289L16.8536 4.14645Z",fill:"currentColor"}),l("path",{d:"M25 22.75V12.5991C24.5572 13.0765 24.053 13.4961 23.5 13.8454V16H17.5L17.3982 16.0068C17.0322 16.0565 16.75 16.3703 16.75 16.75C16.75 18.2688 15.5188 19.5 14 19.5C12.4812 19.5 11.25 18.2688 11.25 16.75L11.2432 16.6482C11.1935 16.2822 10.8797 16 10.5 16H4.5V7.25C4.5 6.2835 5.2835 5.5 6.25 5.5H12.2696C12.4146 4.97463 12.6153 4.47237 12.865 4H6.25C4.45507 4 3 5.45507 3 7.25V22.75C3 24.5449 4.45507 26 6.25 26H21.75C23.5449 26 25 24.5449 25 22.75ZM4.5 22.75V17.5H9.81597L9.85751 17.7041C10.2905 19.5919 11.9808 21 14 21L14.215 20.9947C16.2095 20.8953 17.842 19.4209 18.184 17.5H23.5V22.75C23.5 23.7165 22.7165 24.5 21.75 24.5H6.25C5.2835 24.5 4.5 23.7165 4.5 22.75Z",fill:"currentColor"}))}}),Bn=O("base-close",`
 display: flex;
 align-items: center;
 justify-content: center;
 cursor: pointer;
 background-color: transparent;
 color: var(--n-close-icon-color);
 border-radius: var(--n-close-border-radius);
 height: var(--n-close-size);
 width: var(--n-close-size);
 font-size: var(--n-close-icon-size);
 outline: none;
 border: none;
 position: relative;
 padding: 0;
`,[H("absolute",`
 height: var(--n-close-icon-size);
 width: var(--n-close-icon-size);
 `),j("&::before",`
 content: "";
 position: absolute;
 width: var(--n-close-size);
 height: var(--n-close-size);
 left: 50%;
 top: 50%;
 transform: translateY(-50%) translateX(-50%);
 transition: inherit;
 border-radius: inherit;
 `),me("disabled",[j("&:hover",`
 color: var(--n-close-icon-color-hover);
 `),j("&:hover::before",`
 background-color: var(--n-close-color-hover);
 `),j("&:focus::before",`
 background-color: var(--n-close-color-hover);
 `),j("&:active",`
 color: var(--n-close-icon-color-pressed);
 `),j("&:active::before",`
 background-color: var(--n-close-color-pressed);
 `)]),H("disabled",`
 cursor: not-allowed;
 color: var(--n-close-icon-color-disabled);
 background-color: transparent;
 `),H("round",[j("&::before",`
 border-radius: 50%;
 `)])]),_n=ie({name:"BaseClose",props:{clsPrefix:{type:String,required:!0},disabled:{type:Boolean,default:void 0},focusable:{type:Boolean,default:!0},round:Boolean,onClick:Function,absolute:Boolean},setup(e){return on("-base-close",Bn,X(e,"clsPrefix")),()=>{const{clsPrefix:t,disabled:n,absolute:a,round:i}=e;return l("button",{type:"button",tabindex:n||!e.focusable?-1:0,"aria-disabled":n,"aria-label":"close",disabled:n,class:[`${t}-base-close`,a&&`${t}-base-close--absolute`,n&&`${t}-base-close--disabled`,i&&`${t}-base-close--round`],onMousedown:f=>{e.focusable||f.preventDefault()},onClick:e.onClick},l(io,{clsPrefix:t},{default:()=>l($n,null)}))}}}),Ln={iconSizeSmall:"34px",iconSizeMedium:"40px",iconSizeLarge:"46px",iconSizeHuge:"52px"},En=e=>{const{textColorDisabled:t,iconColor:n,textColor2:a,fontSizeSmall:i,fontSizeMedium:f,fontSizeLarge:d,fontSizeHuge:r}=e;return Object.assign(Object.assign({},Ln),{fontSizeSmall:i,fontSizeMedium:f,fontSizeLarge:d,fontSizeHuge:r,textColor:t,iconColor:n,extraTextColor:a})},An={name:"Empty",common:$e,self:En},So=An,Hn=O("empty",`
 display: flex;
 flex-direction: column;
 align-items: center;
 font-size: var(--n-font-size);
`,[T("icon",`
 width: var(--n-icon-size);
 height: var(--n-icon-size);
 font-size: var(--n-icon-size);
 line-height: var(--n-icon-size);
 color: var(--n-icon-color);
 transition:
 color .3s var(--n-bezier);
 `,[j("+",[T("description",`
 margin-top: 8px;
 `)])]),T("description",`
 transition: color .3s var(--n-bezier);
 color: var(--n-text-color);
 `),T("extra",`
 text-align: center;
 transition: color .3s var(--n-bezier);
 margin-top: 12px;
 color: var(--n-extra-text-color);
 `)]),Dn=Object.assign(Object.assign({},de.props),{description:String,showDescription:{type:Boolean,default:!0},showIcon:{type:Boolean,default:!0},size:{type:String,default:"medium"},renderIcon:Function}),Nn=ie({name:"Empty",props:Dn,setup(e){const{mergedClsPrefixRef:t,inlineThemeDisabled:n}=co(e),a=de("Empty","-empty",Hn,So,e,t),{localeRef:i}=yo("Empty"),f=lo(vn,null),d=B(()=>{var g,y,w;return(g=e.description)!==null&&g!==void 0?g:(w=(y=f==null?void 0:f.mergedComponentPropsRef.value)===null||y===void 0?void 0:y.Empty)===null||w===void 0?void 0:w.description}),r=B(()=>{var g,y;return((y=(g=f==null?void 0:f.mergedComponentPropsRef.value)===null||g===void 0?void 0:g.Empty)===null||y===void 0?void 0:y.renderIcon)||(()=>l(In,null))}),m=B(()=>{const{size:g}=e,{common:{cubicBezierEaseInOut:y},self:{[W("iconSize",g)]:w,[W("fontSize",g)]:p,textColor:u,iconColor:S,extraTextColor:P}}=a.value;return{"--n-icon-size":w,"--n-font-size":p,"--n-bezier":y,"--n-text-color":u,"--n-icon-color":S,"--n-extra-text-color":P}}),x=n?Ie("empty",B(()=>{let g="";const{size:y}=e;return g+=y[0],g}),m,e):void 0;return{mergedClsPrefix:t,mergedRenderIcon:r,localizedDescription:B(()=>d.value||i.value.description),cssVars:n?void 0:m,themeClass:x==null?void 0:x.themeClass,onRender:x==null?void 0:x.onRender}},render(){const{$slots:e,mergedClsPrefix:t,onRender:n}=this;return n==null||n(),l("div",{class:[`${t}-empty`,this.themeClass],style:this.cssVars},this.showIcon?l("div",{class:`${t}-empty__icon`},e.icon?e.icon():l(io,{clsPrefix:t},{default:this.mergedRenderIcon})):null,this.showDescription?l("div",{class:`${t}-empty__description`},e.default?e.default():this.localizedDescription):null,e.extra?l("div",{class:`${t}-empty__extra`},e.extra()):null)}}),Vn={height:"calc(var(--n-option-height) * 7.6)",paddingSmall:"4px 0",paddingMedium:"4px 0",paddingLarge:"4px 0",paddingHuge:"4px 0",optionPaddingSmall:"0 12px",optionPaddingMedium:"0 12px",optionPaddingLarge:"0 12px",optionPaddingHuge:"0 12px",loadingSize:"18px"},Wn=e=>{const{borderRadius:t,popoverColor:n,textColor3:a,dividerColor:i,textColor2:f,primaryColorPressed:d,textColorDisabled:r,primaryColor:m,opacityDisabled:x,hoverColor:g,fontSizeSmall:y,fontSizeMedium:w,fontSizeLarge:p,fontSizeHuge:u,heightSmall:S,heightMedium:P,heightLarge:C,heightHuge:z}=e;return Object.assign(Object.assign({},Vn),{optionFontSizeSmall:y,optionFontSizeMedium:w,optionFontSizeLarge:p,optionFontSizeHuge:u,optionHeightSmall:S,optionHeightMedium:P,optionHeightLarge:C,optionHeightHuge:z,borderRadius:t,color:n,groupHeaderTextColor:a,actionDividerColor:i,optionTextColor:f,optionTextColorPressed:d,optionTextColorDisabled:r,optionTextColorActive:m,optionOpacityDisabled:x,optionCheckColor:m,optionColorPending:g,optionColorActive:"rgba(0, 0, 0, 0)",optionColorActivePending:g,actionTextColor:f,loadingColor:m})},jn=ao({name:"InternalSelectMenu",common:$e,peers:{Scrollbar:rn,Empty:So},self:Wn}),zo=jn,Kn=l(Tn);function Un(e,t){return l(Co,{name:"fade-in-scale-up-transition"},{default:()=>e?l(io,{clsPrefix:t,class:`${t}-base-select-option__check`},{default:()=>Kn}):null})}const bo=ie({name:"NBaseSelectOption",props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0}},setup(e){const{valueRef:t,pendingTmNodeRef:n,multipleRef:a,valueSetRef:i,renderLabelRef:f,renderOptionRef:d,labelFieldRef:r,valueFieldRef:m,showCheckmarkRef:x,nodePropsRef:g,handleOptionClick:y,handleOptionMouseEnter:w}=lo(so),p=Xe(()=>{const{value:C}=n;return C?e.tmNode.key===C.key:!1});function u(C){const{tmNode:z}=e;z.disabled||y(C,z)}function S(C){const{tmNode:z}=e;z.disabled||w(C,z)}function P(C){const{tmNode:z}=e,{value:E}=p;z.disabled||E||w(C,z)}return{multiple:a,isGrouped:Xe(()=>{const{tmNode:C}=e,{parent:z}=C;return z&&z.rawNode.type==="group"}),showCheckmark:x,nodeProps:g,isPending:p,isSelected:Xe(()=>{const{value:C}=t,{value:z}=a;if(C===null)return!1;const E=e.tmNode.rawNode[m.value];if(z){const{value:K}=i;return K.has(E)}else return C===E}),labelField:r,renderLabel:f,renderOption:d,handleMouseMove:P,handleMouseEnter:S,handleClick:u}},render(){const{clsPrefix:e,tmNode:{rawNode:t},isSelected:n,isPending:a,isGrouped:i,showCheckmark:f,nodeProps:d,renderOption:r,renderLabel:m,handleClick:x,handleMouseEnter:g,handleMouseMove:y}=this,w=Un(n,e),p=m?[m(t,n),f&&w]:[Pe(t[this.labelField],t,n),f&&w],u=d==null?void 0:d(t),S=l("div",Object.assign({},u,{class:[`${e}-base-select-option`,t.class,u==null?void 0:u.class,{[`${e}-base-select-option--disabled`]:t.disabled,[`${e}-base-select-option--selected`]:n,[`${e}-base-select-option--grouped`]:i,[`${e}-base-select-option--pending`]:a,[`${e}-base-select-option--show-checkmark`]:f}],style:[(u==null?void 0:u.style)||"",t.style||""],onClick:Je([x,u==null?void 0:u.onClick]),onMouseenter:Je([g,u==null?void 0:u.onMouseenter]),onMousemove:Je([y,u==null?void 0:u.onMousemove])}),l("div",{class:`${e}-base-select-option__content`},p));return t.render?t.render({node:S,option:t,selected:n}):r?r({node:S,option:t,selected:n}):S}}),po=ie({name:"NBaseSelectGroupHeader",props:{clsPrefix:{type:String,required:!0},tmNode:{type:Object,required:!0}},setup(){const{renderLabelRef:e,renderOptionRef:t,labelFieldRef:n,nodePropsRef:a}=lo(so);return{labelField:n,nodeProps:a,renderLabel:e,renderOption:t}},render(){const{clsPrefix:e,renderLabel:t,renderOption:n,nodeProps:a,tmNode:{rawNode:i}}=this,f=a==null?void 0:a(i),d=t?t(i,!1):Pe(i[this.labelField],i,!1),r=l("div",Object.assign({},f,{class:[`${e}-base-select-group-header`,f==null?void 0:f.class]}),d);return i.render?i.render({node:r,option:i}):n?n({node:r,option:i,selected:!1}):r}}),qn=O("base-select-menu",`
 line-height: 1.5;
 outline: none;
 z-index: 0;
 position: relative;
 border-radius: var(--n-border-radius);
 transition:
 background-color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier);
 background-color: var(--n-color);
`,[O("scrollbar",`
 max-height: var(--n-height);
 `),O("virtual-list",`
 max-height: var(--n-height);
 `),O("base-select-option",`
 min-height: var(--n-option-height);
 font-size: var(--n-option-font-size);
 display: flex;
 align-items: center;
 `,[T("content",`
 z-index: 1;
 white-space: nowrap;
 text-overflow: ellipsis;
 overflow: hidden;
 `)]),O("base-select-group-header",`
 min-height: var(--n-option-height);
 font-size: .93em;
 display: flex;
 align-items: center;
 `),O("base-select-menu-option-wrapper",`
 position: relative;
 width: 100%;
 `),T("loading, empty",`
 display: flex;
 padding: 12px 32px;
 flex: 1;
 justify-content: center;
 `),T("loading",`
 color: var(--n-loading-color);
 font-size: var(--n-loading-size);
 `),T("action",`
 padding: 8px var(--n-option-padding-left);
 font-size: var(--n-option-font-size);
 transition: 
 color .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 border-top: 1px solid var(--n-action-divider-color);
 color: var(--n-action-text-color);
 `),O("base-select-group-header",`
 position: relative;
 cursor: default;
 padding: var(--n-option-padding);
 color: var(--n-group-header-text-color);
 `),O("base-select-option",`
 cursor: pointer;
 position: relative;
 padding: var(--n-option-padding);
 transition:
 color .3s var(--n-bezier),
 opacity .3s var(--n-bezier);
 box-sizing: border-box;
 color: var(--n-option-text-color);
 opacity: 1;
 `,[H("show-checkmark",`
 padding-right: calc(var(--n-option-padding-right) + 20px);
 `),j("&::before",`
 content: "";
 position: absolute;
 left: 4px;
 right: 4px;
 top: 0;
 bottom: 0;
 border-radius: var(--n-border-radius);
 transition: background-color .3s var(--n-bezier);
 `),j("&:active",`
 color: var(--n-option-text-color-pressed);
 `),H("grouped",`
 padding-left: calc(var(--n-option-padding-left) * 1.5);
 `),H("pending",[j("&::before",`
 background-color: var(--n-option-color-pending);
 `)]),H("selected",`
 color: var(--n-option-text-color-active);
 `,[j("&::before",`
 background-color: var(--n-option-color-active);
 `),H("pending",[j("&::before",`
 background-color: var(--n-option-color-active-pending);
 `)])]),H("disabled",`
 cursor: not-allowed;
 `,[me("selected",`
 color: var(--n-option-text-color-disabled);
 `),H("selected",`
 opacity: var(--n-option-opacity-disabled);
 `)]),T("check",`
 font-size: 16px;
 position: absolute;
 right: calc(var(--n-option-padding-right) - 4px);
 top: calc(50% - 7px);
 color: var(--n-option-check-color);
 transition: color .3s var(--n-bezier);
 `,[xo({enterScale:"0.5"})])])]),Gn=ie({name:"InternalSelectMenu",props:Object.assign(Object.assign({},de.props),{clsPrefix:{type:String,required:!0},scrollable:{type:Boolean,default:!0},treeMate:{type:Object,required:!0},multiple:Boolean,size:{type:String,default:"medium"},value:{type:[String,Number,Array],default:null},autoPending:Boolean,virtualScroll:{type:Boolean,default:!0},show:{type:Boolean,default:!0},labelField:{type:String,default:"label"},valueField:{type:String,default:"value"},loading:Boolean,focusable:Boolean,renderLabel:Function,renderOption:Function,nodeProps:Function,showCheckmark:{type:Boolean,default:!0},onMousedown:Function,onScroll:Function,onFocus:Function,onBlur:Function,onKeyup:Function,onKeydown:Function,onTabOut:Function,onMouseenter:Function,onMouseleave:Function,onResize:Function,resetMenuOnOptionsChange:{type:Boolean,default:!0},inlineThemeDisabled:Boolean,onToggle:Function}),setup(e){const t=de("InternalSelectMenu","-internal-select-menu",qn,zo,e,X(e,"clsPrefix")),n=$(null),a=$(null),i=$(null),f=B(()=>e.treeMate.getFlattenedNodes()),d=B(()=>zn(f.value)),r=$(null);function m(){const{treeMate:s}=e;let v=null;const{value:A}=e;A===null?v=s.getFirstAvailableNode():(e.multiple?v=s.getNode((A||[])[(A||[]).length-1]):v=s.getNode(A),(!v||v.disabled)&&(v=s.getFirstAvailableNode())),G(v||null)}function x(){const{value:s}=r;s&&!e.treeMate.getNode(s.key)&&(r.value=null)}let g;Te(()=>e.show,s=>{s?g=Te(()=>e.treeMate,()=>{e.resetMenuOnOptionsChange?(e.autoPending?m():x(),ro(V)):x()},{immediate:!0}):g==null||g()},{immediate:!0}),mo(()=>{g==null||g()});const y=B(()=>mn(t.value.self[W("optionHeight",e.size)])),w=B(()=>Ye(t.value.self[W("padding",e.size)])),p=B(()=>e.multiple&&Array.isArray(e.value)?new Set(e.value):new Set),u=B(()=>{const s=f.value;return s&&s.length===0});function S(s){const{onToggle:v}=e;v&&v(s)}function P(s){const{onScroll:v}=e;v&&v(s)}function C(s){var v;(v=i.value)===null||v===void 0||v.sync(),P(s)}function z(){var s;(s=i.value)===null||s===void 0||s.sync()}function E(){const{value:s}=r;return s||null}function K(s,v){v.disabled||G(v,!1)}function U(s,v){v.disabled||S(v)}function L(s){var v;He(s,"action")||(v=e.onKeyup)===null||v===void 0||v.call(e,s)}function D(s){var v;He(s,"action")||(v=e.onKeydown)===null||v===void 0||v.call(e,s)}function q(s){var v;(v=e.onMousedown)===null||v===void 0||v.call(e,s),!e.focusable&&s.preventDefault()}function Q(){const{value:s}=r;s&&G(s.getNext({loop:!0}),!0)}function Y(){const{value:s}=r;s&&G(s.getPrev({loop:!0}),!0)}function G(s,v=!1){r.value=s,v&&V()}function V(){var s,v;const A=r.value;if(!A)return;const re=d.value(A.key);re!==null&&(e.virtualScroll?(s=a.value)===null||s===void 0||s.scrollTo({index:re}):(v=i.value)===null||v===void 0||v.scrollTo({index:re,elSize:y.value}))}function ne(s){var v,A;!((v=n.value)===null||v===void 0)&&v.contains(s.target)&&((A=e.onFocus)===null||A===void 0||A.call(e,s))}function fe(s){var v,A;!((v=n.value)===null||v===void 0)&&v.contains(s.relatedTarget)||(A=e.onBlur)===null||A===void 0||A.call(e,s)}oo(so,{handleOptionMouseEnter:K,handleOptionClick:U,valueSetRef:p,pendingTmNodeRef:r,nodePropsRef:X(e,"nodeProps"),showCheckmarkRef:X(e,"showCheckmark"),multipleRef:X(e,"multiple"),valueRef:X(e,"value"),renderLabelRef:X(e,"renderLabel"),renderOptionRef:X(e,"renderOption"),labelFieldRef:X(e,"labelField"),valueFieldRef:X(e,"valueField")}),oo(ln,n),Ne(()=>{const{value:s}=i;s&&s.sync()});const ue=B(()=>{const{size:s}=e,{common:{cubicBezierEaseInOut:v},self:{height:A,borderRadius:re,color:ve,groupHeaderTextColor:Ce,actionDividerColor:xe,optionTextColorPressed:ge,optionTextColor:he,optionTextColorDisabled:se,optionTextColorActive:J,optionOpacityDisabled:be,optionCheckColor:ce,actionTextColor:Re,optionColorPending:ye,optionColorActive:we,loadingColor:Fe,loadingSize:Oe,optionColorActivePending:Me,[W("optionFontSize",s)]:ze,[W("optionHeight",s)]:ke,[W("optionPadding",s)]:oe}}=t.value;return{"--n-height":A,"--n-action-divider-color":xe,"--n-action-text-color":Re,"--n-bezier":v,"--n-border-radius":re,"--n-color":ve,"--n-option-font-size":ze,"--n-group-header-text-color":Ce,"--n-option-check-color":ce,"--n-option-color-pending":ye,"--n-option-color-active":we,"--n-option-color-active-pending":Me,"--n-option-height":ke,"--n-option-opacity-disabled":be,"--n-option-text-color":he,"--n-option-text-color-active":J,"--n-option-text-color-disabled":se,"--n-option-text-color-pressed":ge,"--n-option-padding":oe,"--n-option-padding-left":Ye(oe,"left"),"--n-option-padding-right":Ye(oe,"right"),"--n-loading-color":Fe,"--n-loading-size":Oe}}),{inlineThemeDisabled:te}=e,ee=te?Ie("internal-select-menu",B(()=>e.size[0]),ue,e):void 0,ae={selfRef:n,next:Q,prev:Y,getPendingTmNode:E};return wo(n,e.onResize),Object.assign({mergedTheme:t,virtualListRef:a,scrollbarRef:i,itemSize:y,padding:w,flattenedNodes:f,empty:u,virtualListContainer(){const{value:s}=a;return s==null?void 0:s.listElRef},virtualListContent(){const{value:s}=a;return s==null?void 0:s.itemsElRef},doScroll:P,handleFocusin:ne,handleFocusout:fe,handleKeyUp:L,handleKeyDown:D,handleMouseDown:q,handleVirtualListResize:z,handleVirtualListScroll:C,cssVars:te?void 0:ue,themeClass:ee==null?void 0:ee.themeClass,onRender:ee==null?void 0:ee.onRender},ae)},render(){const{$slots:e,virtualScroll:t,clsPrefix:n,mergedTheme:a,themeClass:i,onRender:f}=this;return f==null||f(),l("div",{ref:"selfRef",tabindex:this.focusable?0:-1,class:[`${n}-base-select-menu`,i,this.multiple&&`${n}-base-select-menu--multiple`],style:this.cssVars,onFocusin:this.handleFocusin,onFocusout:this.handleFocusout,onKeyup:this.handleKeyUp,onKeydown:this.handleKeyDown,onMousedown:this.handleMouseDown,onMouseenter:this.onMouseenter,onMouseleave:this.onMouseleave},this.loading?l("div",{class:`${n}-base-select-menu__loading`},l(bn,{clsPrefix:n,strokeWidth:20})):this.empty?l("div",{class:`${n}-base-select-menu__empty`,"data-empty":!0},sn(e.empty,()=>[l(Nn,{theme:a.peers.Empty,themeOverrides:a.peerOverrides.Empty})])):l(an,{ref:"scrollbarRef",theme:a.peers.Scrollbar,themeOverrides:a.peerOverrides.Scrollbar,scrollable:this.scrollable,container:t?this.virtualListContainer:void 0,content:t?this.virtualListContent:void 0,onScroll:t?void 0:this.doScroll},{default:()=>t?l(pn,{ref:"virtualListRef",class:`${n}-virtual-list`,items:this.flattenedNodes,itemSize:this.itemSize,showScrollbar:!1,paddingTop:this.padding.top,paddingBottom:this.padding.bottom,onResize:this.handleVirtualListResize,onScroll:this.handleVirtualListScroll,itemResizable:!0},{default:({item:d})=>d.isGroup?l(po,{key:d.key,clsPrefix:n,tmNode:d}):d.ignored?null:l(bo,{clsPrefix:n,key:d.key,tmNode:d})}):l("div",{class:`${n}-base-select-menu-option-wrapper`,style:{paddingTop:this.padding.top,paddingBottom:this.padding.bottom}},this.flattenedNodes.map(d=>d.isGroup?l(po,{key:d.key,clsPrefix:n,tmNode:d}):l(bo,{clsPrefix:n,key:d.key,tmNode:d})))}),no(e.action,d=>d&&[l("div",{class:`${n}-base-select-menu__action`,"data-action":!0,key:"action"},d),l(Cn,{onFocus:this.onTabOut,key:"focus-detector"})]))}}),Zn={closeIconSizeTiny:"12px",closeIconSizeSmall:"12px",closeIconSizeMedium:"14px",closeIconSizeLarge:"14px",closeSizeTiny:"16px",closeSizeSmall:"16px",closeSizeMedium:"18px",closeSizeLarge:"18px",padding:"0 7px",closeMargin:"0 0 0 4px",closeMarginRtl:"0 4px 0 0"},Xn=e=>{const{textColor2:t,primaryColorHover:n,primaryColorPressed:a,primaryColor:i,infoColor:f,successColor:d,warningColor:r,errorColor:m,baseColor:x,borderColor:g,opacityDisabled:y,tagColor:w,closeIconColor:p,closeIconColorHover:u,closeIconColorPressed:S,borderRadiusSmall:P,fontSizeMini:C,fontSizeTiny:z,fontSizeSmall:E,fontSizeMedium:K,heightMini:U,heightTiny:L,heightSmall:D,heightMedium:q,closeColorHover:Q,closeColorPressed:Y,buttonColor2Hover:G,buttonColor2Pressed:V,fontWeightStrong:ne}=e;return Object.assign(Object.assign({},Zn),{closeBorderRadius:P,heightTiny:U,heightSmall:L,heightMedium:D,heightLarge:q,borderRadius:P,opacityDisabled:y,fontSizeTiny:C,fontSizeSmall:z,fontSizeMedium:E,fontSizeLarge:K,fontWeightStrong:ne,textColorCheckable:t,textColorHoverCheckable:t,textColorPressedCheckable:t,textColorChecked:x,colorCheckable:"#0000",colorHoverCheckable:G,colorPressedCheckable:V,colorChecked:i,colorCheckedHover:n,colorCheckedPressed:a,border:`1px solid ${g}`,textColor:t,color:w,colorBordered:"rgb(250, 250, 252)",closeIconColor:p,closeIconColorHover:u,closeIconColorPressed:S,closeColorHover:Q,closeColorPressed:Y,borderPrimary:`1px solid ${I(i,{alpha:.3})}`,textColorPrimary:i,colorPrimary:I(i,{alpha:.12}),colorBorderedPrimary:I(i,{alpha:.1}),closeIconColorPrimary:i,closeIconColorHoverPrimary:i,closeIconColorPressedPrimary:i,closeColorHoverPrimary:I(i,{alpha:.12}),closeColorPressedPrimary:I(i,{alpha:.18}),borderInfo:`1px solid ${I(f,{alpha:.3})}`,textColorInfo:f,colorInfo:I(f,{alpha:.12}),colorBorderedInfo:I(f,{alpha:.1}),closeIconColorInfo:f,closeIconColorHoverInfo:f,closeIconColorPressedInfo:f,closeColorHoverInfo:I(f,{alpha:.12}),closeColorPressedInfo:I(f,{alpha:.18}),borderSuccess:`1px solid ${I(d,{alpha:.3})}`,textColorSuccess:d,colorSuccess:I(d,{alpha:.12}),colorBorderedSuccess:I(d,{alpha:.1}),closeIconColorSuccess:d,closeIconColorHoverSuccess:d,closeIconColorPressedSuccess:d,closeColorHoverSuccess:I(d,{alpha:.12}),closeColorPressedSuccess:I(d,{alpha:.18}),borderWarning:`1px solid ${I(r,{alpha:.35})}`,textColorWarning:r,colorWarning:I(r,{alpha:.15}),colorBorderedWarning:I(r,{alpha:.12}),closeIconColorWarning:r,closeIconColorHoverWarning:r,closeIconColorPressedWarning:r,closeColorHoverWarning:I(r,{alpha:.12}),closeColorPressedWarning:I(r,{alpha:.18}),borderError:`1px solid ${I(m,{alpha:.23})}`,textColorError:m,colorError:I(m,{alpha:.1}),colorBorderedError:I(m,{alpha:.08}),closeIconColorError:m,closeIconColorHoverError:m,closeIconColorPressedError:m,closeColorHoverError:I(m,{alpha:.12}),closeColorPressedError:I(m,{alpha:.18})})},Yn={name:"Tag",common:$e,self:Xn},Jn=Yn,Qn={color:Object,type:{type:String,default:"default"},round:Boolean,size:{type:String,default:"medium"},closable:Boolean,disabled:{type:Boolean,default:void 0}},et=O("tag",`
 white-space: nowrap;
 position: relative;
 box-sizing: border-box;
 cursor: default;
 display: inline-flex;
 align-items: center;
 flex-wrap: nowrap;
 padding: var(--n-padding);
 border-radius: var(--n-border-radius);
 color: var(--n-text-color);
 background-color: var(--n-color);
 transition: 
 border-color .3s var(--n-bezier),
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier),
 opacity .3s var(--n-bezier);
 line-height: 1;
 height: var(--n-height);
 font-size: var(--n-font-size);
`,[H("strong",`
 font-weight: var(--n-font-weight-strong);
 `),T("border",`
 pointer-events: none;
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 border-radius: inherit;
 border: var(--n-border);
 transition: border-color .3s var(--n-bezier);
 `),T("icon",`
 display: flex;
 margin: 0 4px 0 0;
 color: var(--n-text-color);
 transition: color .3s var(--n-bezier);
 font-size: var(--n-avatar-size-override);
 `),T("avatar",`
 display: flex;
 margin: 0 6px 0 0;
 `),T("close",`
 margin: var(--n-close-margin);
 transition:
 background-color .3s var(--n-bezier),
 color .3s var(--n-bezier);
 cursor: pointer;
 `),H("round",`
 padding: 0 calc(var(--n-height) / 3);
 border-radius: calc(var(--n-height) / 2);
 `,[T("icon",`
 margin: 0 4px 0 calc((var(--n-height) - 8px) / -2);
 `),T("avatar",`
 margin: 0 6px 0 calc((var(--n-height) - 8px) / -2);
 `),H("closable",`
 padding: 0 calc(var(--n-height) / 4) 0 calc(var(--n-height) / 3);
 `)]),H("icon, avatar",[H("round",`
 padding: 0 calc(var(--n-height) / 3) 0 calc(var(--n-height) / 2);
 `)]),H("disabled",`
 cursor: not-allowed !important;
 opacity: var(--n-opacity-disabled);
 `),H("checkable",`
 cursor: pointer;
 box-shadow: none;
 color: var(--n-text-color-checkable);
 background-color: var(--n-color-checkable);
 `,[me("disabled",[j("&:hover","background-color: var(--n-color-hover-checkable);",[me("checked","color: var(--n-text-color-hover-checkable);")]),j("&:active","background-color: var(--n-color-pressed-checkable);",[me("checked","color: var(--n-text-color-pressed-checkable);")])]),H("checked",`
 color: var(--n-text-color-checked);
 background-color: var(--n-color-checked);
 `,[me("disabled",[j("&:hover","background-color: var(--n-color-checked-hover);"),j("&:active","background-color: var(--n-color-checked-pressed);")])])])]),ot=Object.assign(Object.assign(Object.assign({},de.props),Qn),{bordered:{type:Boolean,default:void 0},checked:Boolean,checkable:Boolean,strong:Boolean,onClose:[Array,Function],onMouseenter:Function,onMouseleave:Function,"onUpdate:checked":Function,onUpdateChecked:Function,internalCloseFocusable:{type:Boolean,default:!0},internalStopClickPropagation:Boolean,onCheckedChange:{type:Function,validator:()=>!0,default:void 0}}),nt=gn("n-tag"),Qe=ie({name:"Tag",props:ot,setup(e){const t=$(null),{mergedBorderedRef:n,mergedClsPrefixRef:a,inlineThemeDisabled:i,mergedRtlRef:f}=co(e),d=de("Tag","-tag",et,Jn,e,a);oo(nt,{roundRef:X(e,"round")});function r(p){if(!e.disabled&&e.checkable){const{checked:u,onCheckedChange:S,onUpdateChecked:P,"onUpdate:checked":C}=e;P&&P(!u),C&&C(!u),S&&S(!u)}}function m(p){if(e.internalStopClickPropagation&&p.stopPropagation(),!e.disabled){const{onClose:u}=e;u&&le(u,p)}}const x={setTextContent(p){const{value:u}=t;u&&(u.textContent=p)}},g=xn("Tag",f,a),y=B(()=>{const{type:p,size:u,color:{color:S,textColor:P}={}}=e,{common:{cubicBezierEaseInOut:C},self:{padding:z,closeMargin:E,closeMarginRtl:K,borderRadius:U,opacityDisabled:L,textColorCheckable:D,textColorHoverCheckable:q,textColorPressedCheckable:Q,textColorChecked:Y,colorCheckable:G,colorHoverCheckable:V,colorPressedCheckable:ne,colorChecked:fe,colorCheckedHover:ue,colorCheckedPressed:te,closeBorderRadius:ee,fontWeightStrong:ae,[W("colorBordered",p)]:s,[W("closeSize",u)]:v,[W("closeIconSize",u)]:A,[W("fontSize",u)]:re,[W("height",u)]:ve,[W("color",p)]:Ce,[W("textColor",p)]:xe,[W("border",p)]:ge,[W("closeIconColor",p)]:he,[W("closeIconColorHover",p)]:se,[W("closeIconColorPressed",p)]:J,[W("closeColorHover",p)]:be,[W("closeColorPressed",p)]:ce}}=d.value;return{"--n-font-weight-strong":ae,"--n-avatar-size-override":`calc(${ve} - 8px)`,"--n-bezier":C,"--n-border-radius":U,"--n-border":ge,"--n-close-icon-size":A,"--n-close-color-pressed":ce,"--n-close-color-hover":be,"--n-close-border-radius":ee,"--n-close-icon-color":he,"--n-close-icon-color-hover":se,"--n-close-icon-color-pressed":J,"--n-close-icon-color-disabled":he,"--n-close-margin":E,"--n-close-margin-rtl":K,"--n-close-size":v,"--n-color":S||(n.value?s:Ce),"--n-color-checkable":G,"--n-color-checked":fe,"--n-color-checked-hover":ue,"--n-color-checked-pressed":te,"--n-color-hover-checkable":V,"--n-color-pressed-checkable":ne,"--n-font-size":re,"--n-height":ve,"--n-opacity-disabled":L,"--n-padding":z,"--n-text-color":P||xe,"--n-text-color-checkable":D,"--n-text-color-checked":Y,"--n-text-color-hover-checkable":q,"--n-text-color-pressed-checkable":Q}}),w=i?Ie("tag",B(()=>{let p="";const{type:u,size:S,color:{color:P,textColor:C}={}}=e;return p+=u[0],p+=S[0],P&&(p+=`a${vo(P)}`),C&&(p+=`b${vo(C)}`),n.value&&(p+="c"),p}),y,e):void 0;return Object.assign(Object.assign({},x),{rtlEnabled:g,mergedClsPrefix:a,contentRef:t,mergedBordered:n,handleClick:r,handleCloseClick:m,cssVars:i?void 0:y,themeClass:w==null?void 0:w.themeClass,onRender:w==null?void 0:w.onRender})},render(){var e,t;const{mergedClsPrefix:n,rtlEnabled:a,closable:i,color:{borderColor:f}={},round:d,onRender:r,$slots:m}=this;r==null||r();const x=no(m.avatar,y=>y&&l("div",{class:`${n}-tag__avatar`},y)),g=no(m.icon,y=>y&&l("div",{class:`${n}-tag__icon`},y));return l("div",{class:[`${n}-tag`,this.themeClass,{[`${n}-tag--rtl`]:a,[`${n}-tag--strong`]:this.strong,[`${n}-tag--disabled`]:this.disabled,[`${n}-tag--checkable`]:this.checkable,[`${n}-tag--checked`]:this.checkable&&this.checked,[`${n}-tag--round`]:d,[`${n}-tag--avatar`]:x,[`${n}-tag--icon`]:g,[`${n}-tag--closable`]:i}],style:this.cssVars,onClick:this.handleClick,onMouseenter:this.onMouseenter,onMouseleave:this.onMouseleave},g||x,l("span",{class:`${n}-tag__content`,ref:"contentRef"},(t=(e=this.$slots).default)===null||t===void 0?void 0:t.call(e)),!this.checkable&&i?l(_n,{clsPrefix:n,class:`${n}-tag__close`,disabled:this.disabled,onClick:this.handleCloseClick,focusable:this.internalCloseFocusable,round:d,absolute:!0}):null,!this.checkable&&this.mergedBordered?l("div",{class:`${n}-tag__border`,style:{borderColor:f}}):null)}}),tt={paddingSingle:"0 26px 0 12px",paddingMultiple:"3px 26px 0 12px",clearSize:"16px",arrowSize:"16px"},rt=e=>{const{borderRadius:t,textColor2:n,textColorDisabled:a,inputColor:i,inputColorDisabled:f,primaryColor:d,primaryColorHover:r,warningColor:m,warningColorHover:x,errorColor:g,errorColorHover:y,borderColor:w,iconColor:p,iconColorDisabled:u,clearColor:S,clearColorHover:P,clearColorPressed:C,placeholderColor:z,placeholderColorDisabled:E,fontSizeTiny:K,fontSizeSmall:U,fontSizeMedium:L,fontSizeLarge:D,heightTiny:q,heightSmall:Q,heightMedium:Y,heightLarge:G}=e;return Object.assign(Object.assign({},tt),{fontSizeTiny:K,fontSizeSmall:U,fontSizeMedium:L,fontSizeLarge:D,heightTiny:q,heightSmall:Q,heightMedium:Y,heightLarge:G,borderRadius:t,textColor:n,textColorDisabled:a,placeholderColor:z,placeholderColorDisabled:E,color:i,colorDisabled:f,colorActive:i,border:`1px solid ${w}`,borderHover:`1px solid ${r}`,borderActive:`1px solid ${d}`,borderFocus:`1px solid ${r}`,boxShadowHover:"none",boxShadowActive:`0 0 0 2px ${I(d,{alpha:.2})}`,boxShadowFocus:`0 0 0 2px ${I(d,{alpha:.2})}`,caretColor:d,arrowColor:p,arrowColorDisabled:u,loadingColor:d,borderWarning:`1px solid ${m}`,borderHoverWarning:`1px solid ${x}`,borderActiveWarning:`1px solid ${m}`,borderFocusWarning:`1px solid ${x}`,boxShadowHoverWarning:"none",boxShadowActiveWarning:`0 0 0 2px ${I(m,{alpha:.2})}`,boxShadowFocusWarning:`0 0 0 2px ${I(m,{alpha:.2})}`,colorActiveWarning:i,caretColorWarning:m,borderError:`1px solid ${g}`,borderHoverError:`1px solid ${y}`,borderActiveError:`1px solid ${g}`,borderFocusError:`1px solid ${y}`,boxShadowHoverError:"none",boxShadowActiveError:`0 0 0 2px ${I(g,{alpha:.2})}`,boxShadowFocusError:`0 0 0 2px ${I(g,{alpha:.2})}`,colorActiveError:i,caretColorError:g,clearColor:S,clearColorHover:P,clearColorPressed:C})},lt=ao({name:"InternalSelection",common:$e,peers:{Popover:kn},self:rt}),ko=lt,it=j([O("base-selection",`
 position: relative;
 z-index: auto;
 box-shadow: none;
 width: 100%;
 max-width: 100%;
 display: inline-block;
 vertical-align: bottom;
 border-radius: var(--n-border-radius);
 min-height: var(--n-height);
 line-height: 1.5;
 font-size: var(--n-font-size);
 `,[O("base-loading",`
 color: var(--n-loading-color);
 `),O("base-selection-tags","min-height: var(--n-height);"),T("border, state-border",`
 position: absolute;
 left: 0;
 right: 0;
 top: 0;
 bottom: 0;
 pointer-events: none;
 border: var(--n-border);
 border-radius: inherit;
 transition:
 box-shadow .3s var(--n-bezier),
 border-color .3s var(--n-bezier);
 `),T("state-border",`
 z-index: 1;
 border-color: #0000;
 `),O("base-suffix",`
 cursor: pointer;
 position: absolute;
 top: 50%;
 transform: translateY(-50%);
 right: 10px;
 `,[T("arrow",`
 font-size: var(--n-arrow-size);
 color: var(--n-arrow-color);
 transition: color .3s var(--n-bezier);
 `)]),O("base-selection-overlay",`
 display: flex;
 align-items: center;
 white-space: nowrap;
 pointer-events: none;
 position: absolute;
 top: 0;
 right: 0;
 bottom: 0;
 left: 0;
 padding: var(--n-padding-single);
 transition: color .3s var(--n-bezier);
 `,[T("wrapper",`
 flex-basis: 0;
 flex-grow: 1;
 overflow: hidden;
 text-overflow: ellipsis;
 `)]),O("base-selection-placeholder",`
 color: var(--n-placeholder-color);
 `),O("base-selection-tags",`
 cursor: pointer;
 outline: none;
 box-sizing: border-box;
 position: relative;
 z-index: auto;
 display: flex;
 padding: var(--n-padding-multiple);
 flex-wrap: wrap;
 align-items: center;
 width: 100%;
 vertical-align: bottom;
 background-color: var(--n-color);
 border-radius: inherit;
 transition:
 color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 `),O("base-selection-label",`
 height: var(--n-height);
 display: inline-flex;
 width: 100%;
 vertical-align: bottom;
 cursor: pointer;
 outline: none;
 z-index: auto;
 box-sizing: border-box;
 position: relative;
 transition:
 color .3s var(--n-bezier),
 box-shadow .3s var(--n-bezier),
 background-color .3s var(--n-bezier);
 border-radius: inherit;
 background-color: var(--n-color);
 align-items: center;
 `,[O("base-selection-input",`
 font-size: inherit;
 line-height: inherit;
 outline: none;
 cursor: pointer;
 box-sizing: border-box;
 border:none;
 width: 100%;
 padding: var(--n-padding-single);
 background-color: #0000;
 color: var(--n-text-color);
 transition: color .3s var(--n-bezier);
 caret-color: var(--n-caret-color);
 `,[T("content",`
 text-overflow: ellipsis;
 overflow: hidden;
 white-space: nowrap; 
 `)]),T("render-label",`
 color: var(--n-text-color);
 `)]),me("disabled",[j("&:hover",[T("state-border",`
 box-shadow: var(--n-box-shadow-hover);
 border: var(--n-border-hover);
 `)]),H("focus",[T("state-border",`
 box-shadow: var(--n-box-shadow-focus);
 border: var(--n-border-focus);
 `)]),H("active",[T("state-border",`
 box-shadow: var(--n-box-shadow-active);
 border: var(--n-border-active);
 `),O("base-selection-label","background-color: var(--n-color-active);"),O("base-selection-tags","background-color: var(--n-color-active);")])]),H("disabled","cursor: not-allowed;",[T("arrow",`
 color: var(--n-arrow-color-disabled);
 `),O("base-selection-label",`
 cursor: not-allowed;
 background-color: var(--n-color-disabled);
 `,[O("base-selection-input",`
 cursor: not-allowed;
 color: var(--n-text-color-disabled);
 `),T("render-label",`
 color: var(--n-text-color-disabled);
 `)]),O("base-selection-tags",`
 cursor: not-allowed;
 background-color: var(--n-color-disabled);
 `),O("base-selection-placeholder",`
 cursor: not-allowed;
 color: var(--n-placeholder-color-disabled);
 `)]),O("base-selection-input-tag",`
 height: calc(var(--n-height) - 6px);
 line-height: calc(var(--n-height) - 6px);
 outline: none;
 display: none;
 position: relative;
 margin-bottom: 3px;
 max-width: 100%;
 vertical-align: bottom;
 `,[T("input",`
 font-size: inherit;
 font-family: inherit;
 min-width: 1px;
 padding: 0;
 background-color: #0000;
 outline: none;
 border: none;
 max-width: 100%;
 overflow: hidden;
 width: 1em;
 line-height: inherit;
 cursor: pointer;
 color: var(--n-text-color);
 caret-color: var(--n-caret-color);
 `),T("mirror",`
 position: absolute;
 left: 0;
 top: 0;
 white-space: pre;
 visibility: hidden;
 user-select: none;
 -webkit-user-select: none;
 opacity: 0;
 `)]),["warning","error"].map(e=>H(`${e}-status`,[T("state-border",`border: var(--n-border-${e});`),me("disabled",[j("&:hover",[T("state-border",`
 box-shadow: var(--n-box-shadow-hover-${e});
 border: var(--n-border-hover-${e});
 `)]),H("active",[T("state-border",`
 box-shadow: var(--n-box-shadow-active-${e});
 border: var(--n-border-active-${e});
 `),O("base-selection-label",`background-color: var(--n-color-active-${e});`),O("base-selection-tags",`background-color: var(--n-color-active-${e});`)]),H("focus",[T("state-border",`
 box-shadow: var(--n-box-shadow-focus-${e});
 border: var(--n-border-focus-${e});
 `)])])]))]),O("base-selection-popover",`
 margin-bottom: -3px;
 display: flex;
 flex-wrap: wrap;
 margin-right: -8px;
 `),O("base-selection-tag-wrapper",`
 max-width: 100%;
 display: inline-flex;
 padding: 0 7px 3px 0;
 `,[j("&:last-child","padding-right: 0;"),O("tag",`
 font-size: 14px;
 max-width: 100%;
 `,[T("content",`
 line-height: 1.25;
 text-overflow: ellipsis;
 overflow: hidden;
 `)])])]),at=ie({name:"InternalSelection",props:Object.assign(Object.assign({},de.props),{clsPrefix:{type:String,required:!0},bordered:{type:Boolean,default:void 0},active:Boolean,pattern:{type:String,default:""},placeholder:String,selectedOption:{type:Object,default:null},selectedOptions:{type:Array,default:null},labelField:{type:String,default:"label"},valueField:{type:String,default:"value"},multiple:Boolean,filterable:Boolean,clearable:Boolean,disabled:Boolean,size:{type:String,default:"medium"},loading:Boolean,autofocus:Boolean,showArrow:{type:Boolean,default:!0},inputProps:Object,focused:Boolean,renderTag:Function,onKeydown:Function,onClick:Function,onBlur:Function,onFocus:Function,onDeleteOption:Function,maxTagCount:[String,Number],onClear:Function,onPatternInput:Function,onPatternFocus:Function,onPatternBlur:Function,renderLabel:Function,status:String,inlineThemeDisabled:Boolean,onResize:Function}),setup(e){const t=$(null),n=$(null),a=$(null),i=$(null),f=$(null),d=$(null),r=$(null),m=$(null),x=$(null),g=$(null),y=$(!1),w=$(!1),p=$(!1),u=de("InternalSelection","-internal-selection",it,ko,e,X(e,"clsPrefix")),S=B(()=>e.clearable&&!e.disabled&&(p.value||e.active)),P=B(()=>e.selectedOption?e.renderTag?e.renderTag({option:e.selectedOption,handleClose:()=>{}}):e.renderLabel?e.renderLabel(e.selectedOption,!0):Pe(e.selectedOption[e.labelField],e.selectedOption,!0):e.placeholder),C=B(()=>{const c=e.selectedOption;if(!!c)return c[e.labelField]}),z=B(()=>e.multiple?!!(Array.isArray(e.selectedOptions)&&e.selectedOptions.length):e.selectedOption!==null);function E(){var c;const{value:b}=t;if(b){const{value:N}=n;N&&(N.style.width=`${b.offsetWidth}px`,e.maxTagCount!=="responsive"&&((c=x.value)===null||c===void 0||c.sync()))}}function K(){const{value:c}=g;c&&(c.style.display="none")}function U(){const{value:c}=g;c&&(c.style.display="inline-block")}Te(X(e,"active"),c=>{c||K()}),Te(X(e,"pattern"),()=>{e.multiple&&ro(E)});function L(c){const{onFocus:b}=e;b&&b(c)}function D(c){const{onBlur:b}=e;b&&b(c)}function q(c){const{onDeleteOption:b}=e;b&&b(c)}function Q(c){const{onClear:b}=e;b&&b(c)}function Y(c){const{onPatternInput:b}=e;b&&b(c)}function G(c){var b;(!c.relatedTarget||!(!((b=a.value)===null||b===void 0)&&b.contains(c.relatedTarget)))&&L(c)}function V(c){var b;!((b=a.value)===null||b===void 0)&&b.contains(c.relatedTarget)||D(c)}function ne(c){Q(c)}function fe(){p.value=!0}function ue(){p.value=!1}function te(c){!e.active||!e.filterable||c.target!==n.value&&c.preventDefault()}function ee(c){q(c)}function ae(c){if(c.key==="Backspace"&&!s.value&&!e.pattern.length){const{selectedOptions:b}=e;b!=null&&b.length&&ee(b[b.length-1])}}const s=$(!1);let v=null;function A(c){const{value:b}=t;if(b){const N=c.target.value;b.textContent=N,E()}s.value?v=c:Y(c)}function re(){s.value=!0}function ve(){s.value=!1,Y(v),v=null}function Ce(c){var b;w.value=!0,(b=e.onPatternFocus)===null||b===void 0||b.call(e,c)}function xe(c){var b;w.value=!1,(b=e.onPatternBlur)===null||b===void 0||b.call(e,c)}function ge(){var c,b;if(e.filterable)w.value=!1,(c=d.value)===null||c===void 0||c.blur(),(b=n.value)===null||b===void 0||b.blur();else if(e.multiple){const{value:N}=i;N==null||N.blur()}else{const{value:N}=f;N==null||N.blur()}}function he(){var c,b,N;e.filterable?(w.value=!1,(c=d.value)===null||c===void 0||c.focus()):e.multiple?(b=i.value)===null||b===void 0||b.focus():(N=f.value)===null||N===void 0||N.focus()}function se(){const{value:c}=n;c&&(U(),c.focus())}function J(){const{value:c}=n;c&&c.blur()}function be(c){const{value:b}=r;b&&b.setTextContent(`+${c}`)}function ce(){const{value:c}=m;return c}function Re(){return n.value}let ye=null;function we(){ye!==null&&window.clearTimeout(ye)}function Fe(){e.disabled||e.active||(we(),ye=window.setTimeout(()=>{y.value=!0},100))}function Oe(){we()}function Me(c){c||(we(),y.value=!1)}Ne(()=>{Zo(()=>{const c=d.value;!c||(c.tabIndex=e.disabled||w.value?-1:0)})}),wo(a,e.onResize);const{inlineThemeDisabled:ze}=e,ke=B(()=>{const{size:c}=e,{common:{cubicBezierEaseInOut:b},self:{borderRadius:N,color:Be,placeholderColor:Ve,textColor:We,paddingSingle:je,paddingMultiple:Ke,caretColor:_e,colorDisabled:Le,textColorDisabled:Ue,placeholderColorDisabled:qe,colorActive:Ge,boxShadowFocus:Ee,boxShadowActive:pe,boxShadowHover:o,border:h,borderFocus:k,borderHover:_,borderActive:R,arrowColor:M,arrowColorDisabled:F,loadingColor:Z,colorActiveWarning:Ae,boxShadowFocusWarning:Ze,boxShadowActiveWarning:Ro,boxShadowHoverWarning:Fo,borderWarning:Oo,borderFocusWarning:Mo,borderHoverWarning:To,borderActiveWarning:$o,colorActiveError:Io,boxShadowFocusError:Bo,boxShadowActiveError:_o,boxShadowHoverError:Lo,borderError:Eo,borderFocusError:Ao,borderHoverError:Ho,borderActiveError:Do,clearColor:No,clearColorHover:Vo,clearColorPressed:Wo,clearSize:jo,arrowSize:Ko,[W("height",c)]:Uo,[W("fontSize",c)]:qo}}=u.value;return{"--n-bezier":b,"--n-border":h,"--n-border-active":R,"--n-border-focus":k,"--n-border-hover":_,"--n-border-radius":N,"--n-box-shadow-active":pe,"--n-box-shadow-focus":Ee,"--n-box-shadow-hover":o,"--n-caret-color":_e,"--n-color":Be,"--n-color-active":Ge,"--n-color-disabled":Le,"--n-font-size":qo,"--n-height":Uo,"--n-padding-single":je,"--n-padding-multiple":Ke,"--n-placeholder-color":Ve,"--n-placeholder-color-disabled":qe,"--n-text-color":We,"--n-text-color-disabled":Ue,"--n-arrow-color":M,"--n-arrow-color-disabled":F,"--n-loading-color":Z,"--n-color-active-warning":Ae,"--n-box-shadow-focus-warning":Ze,"--n-box-shadow-active-warning":Ro,"--n-box-shadow-hover-warning":Fo,"--n-border-warning":Oo,"--n-border-focus-warning":Mo,"--n-border-hover-warning":To,"--n-border-active-warning":$o,"--n-color-active-error":Io,"--n-box-shadow-focus-error":Bo,"--n-box-shadow-active-error":_o,"--n-box-shadow-hover-error":Lo,"--n-border-error":Eo,"--n-border-focus-error":Ao,"--n-border-hover-error":Ho,"--n-border-active-error":Do,"--n-clear-size":jo,"--n-clear-color":No,"--n-clear-color-hover":Vo,"--n-clear-color-pressed":Wo,"--n-arrow-size":Ko}}),oe=ze?Ie("internal-selection",B(()=>e.size[0]),ke,e):void 0;return{mergedTheme:u,mergedClearable:S,patternInputFocused:w,filterablePlaceholder:P,label:C,selected:z,showTagsPanel:y,isCompositing:s,counterRef:r,counterWrapperRef:m,patternInputMirrorRef:t,patternInputRef:n,selfRef:a,multipleElRef:i,singleElRef:f,patternInputWrapperRef:d,overflowRef:x,inputTagElRef:g,handleMouseDown:te,handleFocusin:G,handleClear:ne,handleMouseEnter:fe,handleMouseLeave:ue,handleDeleteOption:ee,handlePatternKeyDown:ae,handlePatternInputInput:A,handlePatternInputBlur:xe,handlePatternInputFocus:Ce,handleMouseEnterCounter:Fe,handleMouseLeaveCounter:Oe,handleFocusout:V,handleCompositionEnd:ve,handleCompositionStart:re,onPopoverUpdateShow:Me,focus:he,focusInput:se,blur:ge,blurInput:J,updateCounter:be,getCounter:ce,getTail:Re,renderLabel:e.renderLabel,cssVars:ze?void 0:ke,themeClass:oe==null?void 0:oe.themeClass,onRender:oe==null?void 0:oe.onRender}},render(){const{status:e,multiple:t,size:n,disabled:a,filterable:i,maxTagCount:f,bordered:d,clsPrefix:r,onRender:m,renderTag:x,renderLabel:g}=this;m==null||m();const y=f==="responsive",w=typeof f=="number",p=y||w,u=l(cn,null,{default:()=>l(yn,{clsPrefix:r,loading:this.loading,showArrow:this.showArrow,showClear:this.mergedClearable&&this.selected,onClear:this.handleClear},{default:()=>{var P,C;return(C=(P=this.$slots).arrow)===null||C===void 0?void 0:C.call(P)}})});let S;if(t){const{labelField:P}=this,C=V=>l("div",{class:`${r}-base-selection-tag-wrapper`,key:V.value},x?x({option:V,handleClose:()=>this.handleDeleteOption(V)}):l(Qe,{size:n,closable:!V.disabled,disabled:a,onClose:()=>this.handleDeleteOption(V),internalCloseFocusable:!1,internalStopClickPropagation:!0},{default:()=>g?g(V,!0):Pe(V[P],V,!0)})),z=(w?this.selectedOptions.slice(0,f):this.selectedOptions).map(C),E=i?l("div",{class:`${r}-base-selection-input-tag`,ref:"inputTagElRef",key:"__input-tag__"},l("input",Object.assign({},this.inputProps,{ref:"patternInputRef",tabindex:-1,disabled:a,value:this.pattern,autofocus:this.autofocus,class:`${r}-base-selection-input-tag__input`,onBlur:this.handlePatternInputBlur,onFocus:this.handlePatternInputFocus,onKeydown:this.handlePatternKeyDown,onInput:this.handlePatternInputInput,onCompositionstart:this.handleCompositionStart,onCompositionend:this.handleCompositionEnd})),l("span",{ref:"patternInputMirrorRef",class:`${r}-base-selection-input-tag__mirror`},this.pattern)):null,K=y?()=>l("div",{class:`${r}-base-selection-tag-wrapper`,ref:"counterWrapperRef"},l(Qe,{size:n,ref:"counterRef",onMouseenter:this.handleMouseEnterCounter,onMouseleave:this.handleMouseLeaveCounter,disabled:a})):void 0;let U;if(w){const V=this.selectedOptions.length-f;V>0&&(U=l("div",{class:`${r}-base-selection-tag-wrapper`,key:"__counter__"},l(Qe,{size:n,ref:"counterRef",onMouseenter:this.handleMouseEnterCounter,disabled:a},{default:()=>`+${V}`})))}const L=y?i?l(go,{ref:"overflowRef",updateCounter:this.updateCounter,getCounter:this.getCounter,getTail:this.getTail,style:{width:"100%",display:"flex",overflow:"hidden"}},{default:()=>z,counter:K,tail:()=>E}):l(go,{ref:"overflowRef",updateCounter:this.updateCounter,getCounter:this.getCounter,style:{width:"100%",display:"flex",overflow:"hidden"}},{default:()=>z,counter:K}):w?z.concat(U):z,D=p?()=>l("div",{class:`${r}-base-selection-popover`},y?z:this.selectedOptions.map(C)):void 0,q=p?{show:this.showTagsPanel,trigger:"hover",overlap:!0,placement:"top",width:"trigger",onUpdateShow:this.onPopoverUpdateShow,theme:this.mergedTheme.peers.Popover,themeOverrides:this.mergedTheme.peerOverrides.Popover}:null,Y=(this.selected?!1:this.active?!this.pattern&&!this.isCompositing:!0)?l("div",{class:`${r}-base-selection-placeholder ${r}-base-selection-overlay`},this.placeholder):null,G=i?l("div",{ref:"patternInputWrapperRef",class:`${r}-base-selection-tags`},L,y?null:E,u):l("div",{ref:"multipleElRef",class:`${r}-base-selection-tags`,tabindex:a?void 0:0},L,u);S=l(Xo,null,p?l(Pn,Object.assign({},q,{scrollable:!0,style:"max-height: calc(var(--v-target-height) * 6.6);"}),{trigger:()=>G,default:D}):G,Y)}else if(i){const P=this.pattern||this.isCompositing,C=this.active?!P:!this.selected,z=this.active?!1:this.selected;S=l("div",{ref:"patternInputWrapperRef",class:`${r}-base-selection-label`},l("input",Object.assign({},this.inputProps,{ref:"patternInputRef",class:`${r}-base-selection-input`,value:this.active?this.pattern:"",placeholder:"",readonly:a,disabled:a,tabindex:-1,autofocus:this.autofocus,onFocus:this.handlePatternInputFocus,onBlur:this.handlePatternInputBlur,onInput:this.handlePatternInputInput,onCompositionstart:this.handleCompositionStart,onCompositionend:this.handleCompositionEnd})),z?l("div",{class:`${r}-base-selection-label__render-label ${r}-base-selection-overlay`,key:"input"},l("div",{class:`${r}-base-selection-overlay__wrapper`},x?x({option:this.selectedOption,handleClose:()=>{}}):g?g(this.selectedOption,!0):Pe(this.label,this.selectedOption,!0))):null,C?l("div",{class:`${r}-base-selection-placeholder ${r}-base-selection-overlay`,key:"placeholder"},l("div",{class:`${r}-base-selection-overlay__wrapper`},this.filterablePlaceholder)):null,u)}else S=l("div",{ref:"singleElRef",class:`${r}-base-selection-label`,tabindex:this.disabled?void 0:0},this.label!==void 0?l("div",{class:`${r}-base-selection-input`,title:On(this.label),key:"input"},l("div",{class:`${r}-base-selection-input__content`},x?x({option:this.selectedOption,handleClose:()=>{}}):g?g(this.selectedOption,!0):Pe(this.label,this.selectedOption,!0))):l("div",{class:`${r}-base-selection-placeholder ${r}-base-selection-overlay`,key:"placeholder"},this.placeholder),u);return l("div",{ref:"selfRef",class:[`${r}-base-selection`,this.themeClass,e&&`${r}-base-selection--${e}-status`,{[`${r}-base-selection--active`]:this.active,[`${r}-base-selection--selected`]:this.selected||this.active&&this.pattern,[`${r}-base-selection--disabled`]:this.disabled,[`${r}-base-selection--multiple`]:this.multiple,[`${r}-base-selection--focus`]:this.focused}],style:this.cssVars,onClick:this.onClick,onMouseenter:this.handleMouseEnter,onMouseleave:this.handleMouseLeave,onKeydown:this.onKeydown,onFocusin:this.handleFocusin,onFocusout:this.handleFocusout,onMousedown:this.handleMouseDown},S,d?l("div",{class:`${r}-base-selection__border`}):null,d?l("div",{class:`${r}-base-selection__state-border`}):null)}});function De(e){return e.type==="group"}function Po(e){return e.type==="ignored"}function eo(e,t){try{return!!(1+t.toString().toLowerCase().indexOf(e.trim().toLowerCase()))}catch{return!1}}function st(e,t){return{getIsGroup:De,getIgnored:Po,getKey(a){return De(a)?a.name||a.key||"key-required":a[e]},getChildren(a){return a[t]}}}function ct(e,t,n,a){if(!t)return e;function i(f){if(!Array.isArray(f))return[];const d=[];for(const r of f)if(De(r)){const m=i(r[a]);m.length&&d.push(Object.assign({},r,{[a]:m}))}else{if(Po(r))continue;t(n,r)&&d.push(r)}return d}return i(e)}function dt(e,t,n){const a=new Map;return e.forEach(i=>{De(i)?i[n].forEach(f=>{a.set(f[t],f)}):a.set(i[t],i)}),a}function ut(e){const{boxShadow2:t}=e;return{menuBoxShadow:t}}const ht=ao({name:"Select",common:$e,peers:{InternalSelection:ko,InternalSelectMenu:zo},self:ut}),ft=ht,vt=j([O("select",`
 z-index: auto;
 outline: none;
 width: 100%;
 position: relative;
 `),O("select-menu",`
 margin: 4px 0;
 box-shadow: var(--n-menu-box-shadow);
 `,[xo({originalTransition:"background-color .3s var(--n-bezier), box-shadow .3s var(--n-bezier)"})])]),gt=Object.assign(Object.assign({},de.props),{to:to.propTo,bordered:{type:Boolean,default:void 0},clearable:Boolean,clearFilterAfterSelect:{type:Boolean,default:!0},options:{type:Array,default:()=>[]},defaultValue:{type:[String,Number,Array],default:null},value:[String,Number,Array],placeholder:String,menuProps:Object,multiple:Boolean,size:String,filterable:Boolean,disabled:{type:Boolean,default:void 0},remote:Boolean,loading:Boolean,filter:Function,placement:{type:String,default:"bottom-start"},widthMode:{type:String,default:"trigger"},tag:Boolean,onCreate:Function,fallbackOption:{type:[Function,Boolean],default:void 0},show:{type:Boolean,default:void 0},showArrow:{type:Boolean,default:!0},maxTagCount:[Number,String],consistentMenuWidth:{type:Boolean,default:!0},virtualScroll:{type:Boolean,default:!0},labelField:{type:String,default:"label"},valueField:{type:String,default:"value"},childrenField:{type:String,default:"children"},renderLabel:Function,renderOption:Function,renderTag:Function,"onUpdate:value":[Function,Array],inputProps:Object,onUpdateValue:[Function,Array],onBlur:[Function,Array],onClear:[Function,Array],onFocus:[Function,Array],onScroll:[Function,Array],onSearch:[Function,Array],onUpdateShow:[Function,Array],"onUpdate:show":[Function,Array],displayDirective:{type:String,default:"show"},resetMenuOnOptionsChange:{type:Boolean,default:!0},status:String,internalShowCheckmark:{type:Boolean,default:!0},onChange:[Function,Array],items:Array}),wt=ie({name:"Select",props:gt,setup(e){const{mergedClsPrefixRef:t,mergedBorderedRef:n,namespaceRef:a,inlineThemeDisabled:i}=co(e),f=de("Select","-select",vt,ft,e,t),d=$(e.defaultValue),r=X(e,"value"),m=ho(r,d),x=$(!1),g=$(""),y=B(()=>{const{valueField:o,childrenField:h}=e,k=st(o,h);return Fn(V.value,k)}),w=B(()=>dt(Y.value,e.valueField,e.childrenField)),p=$(!1),u=ho(X(e,"show"),p),S=$(null),P=$(null),C=$(null),{localeRef:z}=yo("Select"),E=B(()=>{var o;return(o=e.placeholder)!==null&&o!==void 0?o:z.value.placeholder}),K=Rn(e,["items","options"]),U=[],L=$([]),D=$([]),q=$(new Map),Q=B(()=>{const{fallbackOption:o}=e;if(o===void 0){const{labelField:h,valueField:k}=e;return _=>({[h]:String(_),[k]:_})}return o===!1?!1:h=>Object.assign(o(h),{value:h})}),Y=B(()=>D.value.concat(L.value).concat(K.value)),G=B(()=>{const{filter:o}=e;if(o)return o;const{labelField:h,valueField:k}=e;return(_,R)=>{if(!R)return!1;const M=R[h];if(typeof M=="string")return eo(_,M);const F=R[k];return typeof F=="string"?eo(_,F):typeof F=="number"?eo(_,String(F)):!1}}),V=B(()=>{if(e.remote)return K.value;{const{value:o}=Y,{value:h}=g;return!h.length||!e.filterable?o:ct(o,G.value,h,e.childrenField)}});function ne(o){const h=e.remote,{value:k}=q,{value:_}=w,{value:R}=Q,M=[];return o.forEach(F=>{if(_.has(F))M.push(_.get(F));else if(h&&k.has(F))M.push(k.get(F));else if(R){const Z=R(F);Z&&M.push(Z)}}),M}const fe=B(()=>{if(e.multiple){const{value:o}=m;return Array.isArray(o)?ne(o):[]}return null}),ue=B(()=>{const{value:o}=m;return!e.multiple&&!Array.isArray(o)?o===null?null:ne([o])[0]||null:null}),te=wn(e),{mergedSizeRef:ee,mergedDisabledRef:ae,mergedStatusRef:s}=te;function v(o,h){const{onChange:k,"onUpdate:value":_,onUpdateValue:R}=e,{nTriggerFormChange:M,nTriggerFormInput:F}=te;k&&le(k,o,h),R&&le(R,o,h),_&&le(_,o,h),d.value=o,M(),F()}function A(o){const{onBlur:h}=e,{nTriggerFormBlur:k}=te;h&&le(h,o),k()}function re(){const{onClear:o}=e;o&&le(o)}function ve(o){const{onFocus:h}=e,{nTriggerFormFocus:k}=te;h&&le(h,o),k()}function Ce(o){const{onSearch:h}=e;h&&le(h,o)}function xe(o){const{onScroll:h}=e;h&&le(h,o)}function ge(){var o;const{remote:h,multiple:k}=e;if(h){const{value:_}=q;if(k){const{valueField:R}=e;(o=fe.value)===null||o===void 0||o.forEach(M=>{_.set(M[R],M)})}else{const R=ue.value;R&&_.set(R[e.valueField],R)}}}function he(o){const{onUpdateShow:h,"onUpdate:show":k}=e;h&&le(h,o),k&&le(k,o),p.value=o}function se(){ae.value||(he(!0),p.value=!0,e.filterable&&Ue())}function J(){he(!1)}function be(){g.value="",D.value=U}const ce=$(!1);function Re(){e.filterable&&(ce.value=!0)}function ye(){e.filterable&&(ce.value=!1,u.value||be())}function we(){ae.value||(u.value?e.filterable||J():se())}function Fe(o){var h,k;!((k=(h=C.value)===null||h===void 0?void 0:h.selfRef)===null||k===void 0)&&k.contains(o.relatedTarget)||(x.value=!1,A(o),J())}function Oe(o){ve(o),x.value=!0}function Me(o){x.value=!0}function ze(o){var h;!((h=S.value)===null||h===void 0)&&h.$el.contains(o.relatedTarget)||(x.value=!1,A(o),J())}function ke(){var o;(o=S.value)===null||o===void 0||o.focus(),J()}function oe(o){var h;u.value&&(!((h=S.value)===null||h===void 0)&&h.$el.contains(o.target)||J())}function c(o){if(!Array.isArray(o))return[];if(Q.value)return Array.from(o);{const{remote:h}=e,{value:k}=w;if(h){const{value:_}=q;return o.filter(R=>k.has(R)||_.has(R))}else return o.filter(_=>k.has(_))}}function b(o){N(o.rawNode)}function N(o){if(ae.value)return;const{tag:h,remote:k,clearFilterAfterSelect:_,valueField:R}=e;if(h&&!k){const{value:M}=D,F=M[0]||null;if(F){const Z=L.value;Z.length?Z.push(F):L.value=[F],D.value=U}}if(k&&q.value.set(o[R],o),e.multiple){const M=c(m.value),F=M.findIndex(Z=>Z===o[R]);if(~F){if(M.splice(F,1),h&&!k){const Z=Be(o[R]);~Z&&(L.value.splice(Z,1),_&&(g.value=""))}}else M.push(o[R]),_&&(g.value="");v(M,ne(M))}else{if(h&&!k){const M=Be(o[R]);~M?L.value=[L.value[M]]:L.value=U}Le(),J(),v(o[R],o)}}function Be(o){return L.value.findIndex(k=>k[e.valueField]===o)}function Ve(o){u.value||se();const{value:h}=o.target;g.value=h;const{tag:k,remote:_}=e;if(Ce(h),k&&!_){if(!h){D.value=U;return}const{onCreate:R}=e,M=R?R(h):{[e.labelField]:h,[e.valueField]:h},{valueField:F}=e;K.value.some(Z=>Z[F]===M[F])||L.value.some(Z=>Z[F]===M[F])?D.value=U:D.value=[M]}}function We(o){o.stopPropagation();const{multiple:h}=e;!h&&e.filterable&&J(),re(),h?v([],[]):v(null,null)}function je(o){!He(o,"action")&&!He(o,"empty")&&o.preventDefault()}function Ke(o){xe(o)}function _e(o){var h,k,_,R,M;switch(o.key){case" ":if(e.filterable)break;o.preventDefault();case"Enter":if(!(!((h=S.value)===null||h===void 0)&&h.isCompositing)){if(u.value){const F=(k=C.value)===null||k===void 0?void 0:k.getPendingTmNode();F?b(F):e.filterable||(J(),Le())}else if(se(),e.tag&&ce.value){const F=D.value[0];if(F){const Z=F[e.valueField],{value:Ae}=m;e.multiple&&Array.isArray(Ae)&&Ae.some(Ze=>Ze===Z)||N(F)}}}o.preventDefault();break;case"ArrowUp":if(o.preventDefault(),e.loading)return;u.value&&((_=C.value)===null||_===void 0||_.prev());break;case"ArrowDown":if(o.preventDefault(),e.loading)return;u.value?(R=C.value)===null||R===void 0||R.next():se();break;case"Escape":u.value&&(Sn(o),J()),(M=S.value)===null||M===void 0||M.focus();break}}function Le(){var o;(o=S.value)===null||o===void 0||o.focus()}function Ue(){var o;(o=S.value)===null||o===void 0||o.focusInput()}function qe(){var o;!u.value||(o=P.value)===null||o===void 0||o.syncPosition()}ge(),Te(X(e,"options"),ge);const Ge={focus:()=>{var o;(o=S.value)===null||o===void 0||o.focus()},blur:()=>{var o;(o=S.value)===null||o===void 0||o.blur()}},Ee=B(()=>{const{self:{menuBoxShadow:o}}=f.value;return{"--n-menu-box-shadow":o}}),pe=i?Ie("select",void 0,Ee,e):void 0;return Object.assign(Object.assign({},Ge),{mergedStatus:s,mergedClsPrefix:t,mergedBordered:n,namespace:a,treeMate:y,isMounted:dn(),triggerRef:S,menuRef:C,pattern:g,uncontrolledShow:p,mergedShow:u,adjustedTo:to(e),uncontrolledValue:d,mergedValue:m,followerRef:P,localizedPlaceholder:E,selectedOption:ue,selectedOptions:fe,mergedSize:ee,mergedDisabled:ae,focused:x,activeWithoutMenuOpen:ce,inlineThemeDisabled:i,onTriggerInputFocus:Re,onTriggerInputBlur:ye,handleTriggerOrMenuResize:qe,handleMenuFocus:Me,handleMenuBlur:ze,handleMenuTabOut:ke,handleTriggerClick:we,handleToggle:b,handleDeleteOption:N,handlePatternInput:Ve,handleClear:We,handleTriggerBlur:Fe,handleTriggerFocus:Oe,handleKeydown:_e,handleMenuAfterLeave:be,handleMenuClickOutside:oe,handleMenuScroll:Ke,handleMenuKeydown:_e,handleMenuMousedown:je,mergedTheme:f,cssVars:i?void 0:Ee,themeClass:pe==null?void 0:pe.themeClass,onRender:pe==null?void 0:pe.onRender})},render(){return l("div",{class:`${this.mergedClsPrefix}-select`},l(un,null,{default:()=>[l(hn,null,{default:()=>l(at,{ref:"triggerRef",inlineThemeDisabled:this.inlineThemeDisabled,status:this.mergedStatus,inputProps:this.inputProps,clsPrefix:this.mergedClsPrefix,showArrow:this.showArrow,maxTagCount:this.maxTagCount,bordered:this.mergedBordered,active:this.activeWithoutMenuOpen||this.mergedShow,pattern:this.pattern,placeholder:this.localizedPlaceholder,selectedOption:this.selectedOption,selectedOptions:this.selectedOptions,multiple:this.multiple,renderTag:this.renderTag,renderLabel:this.renderLabel,filterable:this.filterable,clearable:this.clearable,disabled:this.mergedDisabled,size:this.mergedSize,theme:this.mergedTheme.peers.InternalSelection,labelField:this.labelField,valueField:this.valueField,themeOverrides:this.mergedTheme.peerOverrides.InternalSelection,loading:this.loading,focused:this.focused,onClick:this.handleTriggerClick,onDeleteOption:this.handleDeleteOption,onPatternInput:this.handlePatternInput,onClear:this.handleClear,onBlur:this.handleTriggerBlur,onFocus:this.handleTriggerFocus,onKeydown:this.handleKeydown,onPatternBlur:this.onTriggerInputBlur,onPatternFocus:this.onTriggerInputFocus,onResize:this.handleTriggerOrMenuResize},{arrow:()=>{var e,t;return[(t=(e=this.$slots).arrow)===null||t===void 0?void 0:t.call(e)]}})}),l(fn,{ref:"followerRef",show:this.mergedShow,to:this.adjustedTo,teleportDisabled:this.adjustedTo===to.tdkey,containerClass:this.namespace,width:this.consistentMenuWidth?"target":void 0,minWidth:"target",placement:this.placement},{default:()=>l(Co,{name:"fade-in-scale-up-transition",appear:this.isMounted,onAfterLeave:this.handleMenuAfterLeave},{default:()=>{var e,t,n;return this.mergedShow||this.displayDirective==="show"?((e=this.onRender)===null||e===void 0||e.call(this),Yo(l(Gn,Object.assign({},this.menuProps,{ref:"menuRef",onResize:this.handleTriggerOrMenuResize,inlineThemeDisabled:this.inlineThemeDisabled,virtualScroll:this.consistentMenuWidth&&this.virtualScroll,class:[`${this.mergedClsPrefix}-select-menu`,this.themeClass,(t=this.menuProps)===null||t===void 0?void 0:t.class],clsPrefix:this.mergedClsPrefix,focusable:!0,labelField:this.labelField,valueField:this.valueField,autoPending:!0,theme:this.mergedTheme.peers.InternalSelectMenu,themeOverrides:this.mergedTheme.peerOverrides.InternalSelectMenu,treeMate:this.treeMate,multiple:this.multiple,size:"medium",renderOption:this.renderOption,renderLabel:this.renderLabel,value:this.mergedValue,style:[(n=this.menuProps)===null||n===void 0?void 0:n.style,this.cssVars],onToggle:this.handleToggle,onScroll:this.handleMenuScroll,onFocus:this.handleMenuFocus,onBlur:this.handleMenuBlur,onKeydown:this.handleMenuKeydown,onTabOut:this.handleMenuTabOut,onMousedown:this.handleMenuMousedown,show:this.mergedShow,showCheckmark:this.internalShowCheckmark,resetMenuOnOptionsChange:this.resetMenuOnOptionsChange}),{empty:()=>{var a,i;return[(i=(a=this.$slots).empty)===null||i===void 0?void 0:i.call(a)]},action:()=>{var a,i;return[(i=(a=this.$slots).action)===null||i===void 0?void 0:i.call(a)]}}),this.displayDirective==="show"?[[Jo,this.mergedShow],[fo,this.handleMenuClickOutside,void 0,{capture:!0}]]:[[fo,this.handleMenuClickOutside,void 0,{capture:!0}]])):null}})})]}))}});export{wt as N};

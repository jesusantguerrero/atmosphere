import{o as dn,f as fn,b as hn,y as pn,B as gn,ad as mn,r as xe,J as je,l as I,R as bn}from"./app-114b751b.js";import{aX as He,aY as wn,aZ as yn}from"./isMobile-e3872359.js";import{_ as In}from"./_plugin-vue_export-helper-c27b6911.js";const En={name:"IcSharpSyncAlt"},_n={xmlns:"http://www.w3.org/2000/svg","xmlns:xlink":"http://www.w3.org/1999/xlink",width:"1em",height:"1em",viewBox:"0 0 24 24"},vn=hn("path",{fill:"currentColor",d:"m18 12l4-4l-4-4v3H3v2h15zM6 12l-4 4l4 4v-3h15v-2H6z"},null,-1),Sn=[vn];function An(e,t,n,r,i,o){return dn(),fn("svg",_n,Sn)}const Tn=In(En,[["render",An]]),P=pn({isOpen:!1,data:null}),Bs=()=>{const e=()=>{P.isOpen=!1,P.data=null},t=(i={})=>{P.data=i.data??null,P.isOpen=!0},n=i=>{P.isOpen?e():t(i)},{isOpen:r}=gn(P);return{toggleModal:n,openModal:t,closeModal:e,isOpen:r}},Rs=mn("context",()=>{const e=xe(He()),{width:t}=wn(),n=xe(!1),r=(c=null)=>{const l=c??!n.value;n.value=l};je(t,()=>{e.value=He()});const{isFullscreen:i,enter:o,exit:s,toggle:a}=yn();return je(e,c=>{c?s():o()}),{isMobile:e,isMoreOptionsModalOpen:n,toggleOptionsModal:r,toggleFullscreen:a,enterFullscreen:o,exitFullscreen:s,isFullscreen:i}});const Y={HOUSING:"housing",MEAL:"meal",FINANCE:"finance",RELATIONSHIP:"relationship"},Dn={[Y.HOUSING]:[{label:"Overview",url:"/housing"},{label:"Chores",url:"/housing/chores"},{label:"Occurrence Checks",url:"/housing/occurrence"},{label:"Plans",url:"/housing/plans"},{label:"Equipment",url:"/housing/equipments"}],[Y.MEAL]:[{label:"Overview",url:"/meals/overview"},{label:"Planner",url:"/meal-planner"},{label:"Recipes",url:"/meals"},{label:"Ingredients",url:"/ingredients"},{label:"Menus",url:"/meals/menus",hidden:!0}],[Y.FINANCE]:[{label:"Overview",url:"/finance"},{label:"Budget & Goals",url:"/budgets"},{label:"Watchlist",url:"/finance/watchlist"},{label:"Transactions",url:"/finance/transactions"},{label:"Trends",url:"/trends"}]},$s=e=>Dn[e].filter(t=>!t.hidden),Ps=e=>{const t=[{icon:"fa fa-home",name:"home",label:e("Home"),to:"/dashboard",as:I},{icon:"far fa-calendar-alt",label:e("Meals"),name:"mealPlanner",to:"/meals/overview",as:I,isActiveFunction(i,o){return/meal-planner|meals|ingredients/.test(o)}},{icon:"fas fa-dollar-sign",label:e("Finance"),name:"finance",to:"/finance",as:I,isActiveFunction(i,o){return/finance|budgets|trends/.test(o)}},{icon:"fas fa-heart",label:e("Relationship"),to:"/relationships",hidden:!0,as:I},{icon:"fas fa-home",label:e("Housing"),to:"/housing",as:I,isActiveFunction(i,o){return/housing/.test(o)}}].filter(i=>!i.hidden);let n=bn.cloneDeep(t);n.splice(2,null,{name:"add",label:"Add",icon:Tn,action:"openTransactionModal"});const r=[{icon:"fa fa-heart",name:"support",label:e("Support project"),to:"/settings/support",as:I},{icon:"fas fa-info",label:e("About"),to:"/settings/about",as:I},{icon:"fas fa-question",label:e("Help Center"),to:"/settings/help",as:I},{icon:"fas fa-cogs",label:e("Settings"),name:"settings",to:"/settings",as:I}];return{appMenu:t,mobileMenu:n,headerMenu:r}},Ns="UTC",Ls=["dd MMM, yyyy","dd.MM.yyyy","MM/dd/yyyy","yyyy.MM.dd"],Fs=(e,t="team_")=>{const n=new RegExp(t);return e.settings.reduce((r,i)=>{const o=i.name.replace(n,"");return r[o]=i.value,r},{name:e.name})},xs=(e,t="team_")=>Object.keys(e).reduce((n,r)=>(n[t+r]=e[r],n),{name:e.name});/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *//**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const bt=function(e){const t=[];let n=0;for(let r=0;r<e.length;r++){let i=e.charCodeAt(r);i<128?t[n++]=i:i<2048?(t[n++]=i>>6|192,t[n++]=i&63|128):(i&64512)===55296&&r+1<e.length&&(e.charCodeAt(r+1)&64512)===56320?(i=65536+((i&1023)<<10)+(e.charCodeAt(++r)&1023),t[n++]=i>>18|240,t[n++]=i>>12&63|128,t[n++]=i>>6&63|128,t[n++]=i&63|128):(t[n++]=i>>12|224,t[n++]=i>>6&63|128,t[n++]=i&63|128)}return t},Cn=function(e){const t=[];let n=0,r=0;for(;n<e.length;){const i=e[n++];if(i<128)t[r++]=String.fromCharCode(i);else if(i>191&&i<224){const o=e[n++];t[r++]=String.fromCharCode((i&31)<<6|o&63)}else if(i>239&&i<365){const o=e[n++],s=e[n++],a=e[n++],c=((i&7)<<18|(o&63)<<12|(s&63)<<6|a&63)-65536;t[r++]=String.fromCharCode(55296+(c>>10)),t[r++]=String.fromCharCode(56320+(c&1023))}else{const o=e[n++],s=e[n++];t[r++]=String.fromCharCode((i&15)<<12|(o&63)<<6|s&63)}}return t.join("")},wt={byteToCharMap_:null,charToByteMap_:null,byteToCharMapWebSafe_:null,charToByteMapWebSafe_:null,ENCODED_VALS_BASE:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789",get ENCODED_VALS(){return this.ENCODED_VALS_BASE+"+/="},get ENCODED_VALS_WEBSAFE(){return this.ENCODED_VALS_BASE+"-_."},HAS_NATIVE_SUPPORT:typeof atob=="function",encodeByteArray(e,t){if(!Array.isArray(e))throw Error("encodeByteArray takes an array as a parameter");this.init_();const n=t?this.byteToCharMapWebSafe_:this.byteToCharMap_,r=[];for(let i=0;i<e.length;i+=3){const o=e[i],s=i+1<e.length,a=s?e[i+1]:0,c=i+2<e.length,l=c?e[i+2]:0,u=o>>2,p=(o&3)<<4|a>>4;let y=(a&15)<<2|l>>6,g=l&63;c||(g=64,s||(y=64)),r.push(n[u],n[p],n[y],n[g])}return r.join("")},encodeString(e,t){return this.HAS_NATIVE_SUPPORT&&!t?btoa(e):this.encodeByteArray(bt(e),t)},decodeString(e,t){return this.HAS_NATIVE_SUPPORT&&!t?atob(e):Cn(this.decodeStringToByteArray(e,t))},decodeStringToByteArray(e,t){this.init_();const n=t?this.charToByteMapWebSafe_:this.charToByteMap_,r=[];for(let i=0;i<e.length;){const o=n[e.charAt(i++)],a=i<e.length?n[e.charAt(i)]:0;++i;const l=i<e.length?n[e.charAt(i)]:64;++i;const p=i<e.length?n[e.charAt(i)]:64;if(++i,o==null||a==null||l==null||p==null)throw new Mn;const y=o<<2|a>>4;if(r.push(y),l!==64){const g=a<<4&240|l>>2;if(r.push(g),p!==64){const un=l<<6&192|p;r.push(un)}}}return r},init_(){if(!this.byteToCharMap_){this.byteToCharMap_={},this.charToByteMap_={},this.byteToCharMapWebSafe_={},this.charToByteMapWebSafe_={};for(let e=0;e<this.ENCODED_VALS.length;e++)this.byteToCharMap_[e]=this.ENCODED_VALS.charAt(e),this.charToByteMap_[this.byteToCharMap_[e]]=e,this.byteToCharMapWebSafe_[e]=this.ENCODED_VALS_WEBSAFE.charAt(e),this.charToByteMapWebSafe_[this.byteToCharMapWebSafe_[e]]=e,e>=this.ENCODED_VALS_BASE.length&&(this.charToByteMap_[this.ENCODED_VALS_WEBSAFE.charAt(e)]=e,this.charToByteMapWebSafe_[this.ENCODED_VALS.charAt(e)]=e)}}};class Mn extends Error{constructor(){super(...arguments),this.name="DecodeBase64StringError"}}const On=function(e){const t=bt(e);return wt.encodeByteArray(t,!0)},yt=function(e){return On(e).replace(/\./g,"")},kn=function(e){try{return wt.decodeString(e,!0)}catch(t){console.error("base64Decode failed: ",t)}return null};/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Bn(){if(typeof self<"u")return self;if(typeof window<"u")return window;if(typeof global<"u")return global;throw new Error("Unable to locate global object.")}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Rn=()=>Bn().__FIREBASE_DEFAULTS__,$n=()=>{if(typeof process>"u"||typeof process.env>"u")return;const e={}.__FIREBASE_DEFAULTS__;if(e)return JSON.parse(e)},Pn=()=>{if(typeof document>"u")return;let e;try{e=document.cookie.match(/__FIREBASE_DEFAULTS__=([^;]+)/)}catch{return}const t=e&&kn(e[1]);return t&&JSON.parse(t)},Nn=()=>{try{return Rn()||$n()||Pn()}catch(e){console.info(`Unable to get __FIREBASE_DEFAULTS__ due to: ${e}`);return}},It=()=>{var e;return(e=Nn())===null||e===void 0?void 0:e.config};/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Ln{constructor(){this.reject=()=>{},this.resolve=()=>{},this.promise=new Promise((t,n)=>{this.resolve=t,this.reject=n})}wrapCallback(t){return(n,r)=>{n?this.reject(n):this.resolve(r),typeof t=="function"&&(this.promise.catch(()=>{}),t.length===1?t(n):t(n,r))}}}function Fn(){const e=typeof chrome=="object"?chrome.runtime:typeof browser=="object"?browser.runtime:void 0;return typeof e=="object"&&e.id!==void 0}function ve(){try{return typeof indexedDB=="object"}catch{return!1}}function Se(){return new Promise((e,t)=>{try{let n=!0;const r="validate-browser-context-for-indexeddb-analytics-module",i=self.indexedDB.open(r);i.onsuccess=()=>{i.result.close(),n||self.indexedDB.deleteDatabase(r),e(!0)},i.onupgradeneeded=()=>{n=!1},i.onerror=()=>{var o;t(((o=i.error)===null||o===void 0?void 0:o.message)||"")}}catch(n){t(n)}})}function Et(){return!(typeof navigator>"u"||!navigator.cookieEnabled)}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const xn="FirebaseError";class $ extends Error{constructor(t,n,r){super(n),this.code=t,this.customData=r,this.name=xn,Object.setPrototypeOf(this,$.prototype),Error.captureStackTrace&&Error.captureStackTrace(this,x.prototype.create)}}class x{constructor(t,n,r){this.service=t,this.serviceName=n,this.errors=r}create(t,...n){const r=n[0]||{},i=`${this.service}/${t}`,o=this.errors[t],s=o?jn(o,r):"Error",a=`${this.serviceName}: ${s} (${i}).`;return new $(i,a,r)}}function jn(e,t){return e.replace(Hn,(n,r)=>{const i=t[r];return i!=null?String(i):`<${r}?>`})}const Hn=/\{\$([^}]+)}/g;function V(e,t){if(e===t)return!0;const n=Object.keys(e),r=Object.keys(t);for(const i of n){if(!r.includes(i))return!1;const o=e[i],s=t[i];if(Ve(o)&&Ve(s)){if(!V(o,s))return!1}else if(o!==s)return!1}for(const i of r)if(!n.includes(i))return!1;return!0}function Ve(e){return e!==null&&typeof e=="object"}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Vn=1e3,Un=2,Kn=4*60*60*1e3,Wn=.5;function Ue(e,t=Vn,n=Un){const r=t*Math.pow(n,e),i=Math.round(Wn*r*(Math.random()-.5)*2);return Math.min(Kn,r+i)}/**
 * @license
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function j(e){return e&&e._delegate?e._delegate:e}class w{constructor(t,n,r){this.name=t,this.instanceFactory=n,this.type=r,this.multipleInstances=!1,this.serviceProps={},this.instantiationMode="LAZY",this.onInstanceCreated=null}setInstantiationMode(t){return this.instantiationMode=t,this}setMultipleInstances(t){return this.multipleInstances=t,this}setServiceProps(t){return this.serviceProps=t,this}setInstanceCreatedCallback(t){return this.onInstanceCreated=t,this}}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const C="[DEFAULT]";/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class zn{constructor(t,n){this.name=t,this.container=n,this.component=null,this.instances=new Map,this.instancesDeferred=new Map,this.instancesOptions=new Map,this.onInitCallbacks=new Map}get(t){const n=this.normalizeInstanceIdentifier(t);if(!this.instancesDeferred.has(n)){const r=new Ln;if(this.instancesDeferred.set(n,r),this.isInitialized(n)||this.shouldAutoInitialize())try{const i=this.getOrInitializeService({instanceIdentifier:n});i&&r.resolve(i)}catch{}}return this.instancesDeferred.get(n).promise}getImmediate(t){var n;const r=this.normalizeInstanceIdentifier(t==null?void 0:t.identifier),i=(n=t==null?void 0:t.optional)!==null&&n!==void 0?n:!1;if(this.isInitialized(r)||this.shouldAutoInitialize())try{return this.getOrInitializeService({instanceIdentifier:r})}catch(o){if(i)return null;throw o}else{if(i)return null;throw Error(`Service ${this.name} is not available`)}}getComponent(){return this.component}setComponent(t){if(t.name!==this.name)throw Error(`Mismatching Component ${t.name} for Provider ${this.name}.`);if(this.component)throw Error(`Component for ${this.name} has already been provided`);if(this.component=t,!!this.shouldAutoInitialize()){if(qn(t))try{this.getOrInitializeService({instanceIdentifier:C})}catch{}for(const[n,r]of this.instancesDeferred.entries()){const i=this.normalizeInstanceIdentifier(n);try{const o=this.getOrInitializeService({instanceIdentifier:i});r.resolve(o)}catch{}}}}clearInstance(t=C){this.instancesDeferred.delete(t),this.instancesOptions.delete(t),this.instances.delete(t)}async delete(){const t=Array.from(this.instances.values());await Promise.all([...t.filter(n=>"INTERNAL"in n).map(n=>n.INTERNAL.delete()),...t.filter(n=>"_delete"in n).map(n=>n._delete())])}isComponentSet(){return this.component!=null}isInitialized(t=C){return this.instances.has(t)}getOptions(t=C){return this.instancesOptions.get(t)||{}}initialize(t={}){const{options:n={}}=t,r=this.normalizeInstanceIdentifier(t.instanceIdentifier);if(this.isInitialized(r))throw Error(`${this.name}(${r}) has already been initialized`);if(!this.isComponentSet())throw Error(`Component ${this.name} has not been registered yet`);const i=this.getOrInitializeService({instanceIdentifier:r,options:n});for(const[o,s]of this.instancesDeferred.entries()){const a=this.normalizeInstanceIdentifier(o);r===a&&s.resolve(i)}return i}onInit(t,n){var r;const i=this.normalizeInstanceIdentifier(n),o=(r=this.onInitCallbacks.get(i))!==null&&r!==void 0?r:new Set;o.add(t),this.onInitCallbacks.set(i,o);const s=this.instances.get(i);return s&&t(s,i),()=>{o.delete(t)}}invokeOnInitCallbacks(t,n){const r=this.onInitCallbacks.get(n);if(r)for(const i of r)try{i(t,n)}catch{}}getOrInitializeService({instanceIdentifier:t,options:n={}}){let r=this.instances.get(t);if(!r&&this.component&&(r=this.component.instanceFactory(this.container,{instanceIdentifier:Gn(t),options:n}),this.instances.set(t,r),this.instancesOptions.set(t,n),this.invokeOnInitCallbacks(r,t),this.component.onInstanceCreated))try{this.component.onInstanceCreated(this.container,t,r)}catch{}return r||null}normalizeInstanceIdentifier(t=C){return this.component?this.component.multipleInstances?t:C:t}shouldAutoInitialize(){return!!this.component&&this.component.instantiationMode!=="EXPLICIT"}}function Gn(e){return e===C?void 0:e}function qn(e){return e.instantiationMode==="EAGER"}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Yn{constructor(t){this.name=t,this.providers=new Map}addComponent(t){const n=this.getProvider(t.name);if(n.isComponentSet())throw new Error(`Component ${t.name} has already been registered with ${this.name}`);n.setComponent(t)}addOrOverwriteComponent(t){this.getProvider(t.name).isComponentSet()&&this.providers.delete(t.name),this.addComponent(t)}getProvider(t){if(this.providers.has(t))return this.providers.get(t);const n=new zn(t,this);return this.providers.set(t,n),n}getProviders(){return Array.from(this.providers.values())}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */var d;(function(e){e[e.DEBUG=0]="DEBUG",e[e.VERBOSE=1]="VERBOSE",e[e.INFO=2]="INFO",e[e.WARN=3]="WARN",e[e.ERROR=4]="ERROR",e[e.SILENT=5]="SILENT"})(d||(d={}));const Jn={debug:d.DEBUG,verbose:d.VERBOSE,info:d.INFO,warn:d.WARN,error:d.ERROR,silent:d.SILENT},Xn=d.INFO,Zn={[d.DEBUG]:"log",[d.VERBOSE]:"log",[d.INFO]:"info",[d.WARN]:"warn",[d.ERROR]:"error"},Qn=(e,t,...n)=>{if(t<e.logLevel)return;const r=new Date().toISOString(),i=Zn[t];if(i)console[i](`[${r}]  ${e.name}:`,...n);else throw new Error(`Attempted to log a message with an invalid logType (value: ${t})`)};class _t{constructor(t){this.name=t,this._logLevel=Xn,this._logHandler=Qn,this._userLogHandler=null}get logLevel(){return this._logLevel}set logLevel(t){if(!(t in d))throw new TypeError(`Invalid value "${t}" assigned to \`logLevel\``);this._logLevel=t}setLogLevel(t){this._logLevel=typeof t=="string"?Jn[t]:t}get logHandler(){return this._logHandler}set logHandler(t){if(typeof t!="function")throw new TypeError("Value assigned to `logHandler` must be a function");this._logHandler=t}get userLogHandler(){return this._userLogHandler}set userLogHandler(t){this._userLogHandler=t}debug(...t){this._userLogHandler&&this._userLogHandler(this,d.DEBUG,...t),this._logHandler(this,d.DEBUG,...t)}log(...t){this._userLogHandler&&this._userLogHandler(this,d.VERBOSE,...t),this._logHandler(this,d.VERBOSE,...t)}info(...t){this._userLogHandler&&this._userLogHandler(this,d.INFO,...t),this._logHandler(this,d.INFO,...t)}warn(...t){this._userLogHandler&&this._userLogHandler(this,d.WARN,...t),this._logHandler(this,d.WARN,...t)}error(...t){this._userLogHandler&&this._userLogHandler(this,d.ERROR,...t),this._logHandler(this,d.ERROR,...t)}}const er=(e,t)=>t.some(n=>e instanceof n);let Ke,We;function tr(){return Ke||(Ke=[IDBDatabase,IDBObjectStore,IDBIndex,IDBCursor,IDBTransaction])}function nr(){return We||(We=[IDBCursor.prototype.advance,IDBCursor.prototype.continue,IDBCursor.prototype.continuePrimaryKey])}const vt=new WeakMap,he=new WeakMap,St=new WeakMap,J=new WeakMap,Ae=new WeakMap;function rr(e){const t=new Promise((n,r)=>{const i=()=>{e.removeEventListener("success",o),e.removeEventListener("error",s)},o=()=>{n(A(e.result)),i()},s=()=>{r(e.error),i()};e.addEventListener("success",o),e.addEventListener("error",s)});return t.then(n=>{n instanceof IDBCursor&&vt.set(n,e)}).catch(()=>{}),Ae.set(t,e),t}function ir(e){if(he.has(e))return;const t=new Promise((n,r)=>{const i=()=>{e.removeEventListener("complete",o),e.removeEventListener("error",s),e.removeEventListener("abort",s)},o=()=>{n(),i()},s=()=>{r(e.error||new DOMException("AbortError","AbortError")),i()};e.addEventListener("complete",o),e.addEventListener("error",s),e.addEventListener("abort",s)});he.set(e,t)}let pe={get(e,t,n){if(e instanceof IDBTransaction){if(t==="done")return he.get(e);if(t==="objectStoreNames")return e.objectStoreNames||St.get(e);if(t==="store")return n.objectStoreNames[1]?void 0:n.objectStore(n.objectStoreNames[0])}return A(e[t])},set(e,t,n){return e[t]=n,!0},has(e,t){return e instanceof IDBTransaction&&(t==="done"||t==="store")?!0:t in e}};function or(e){pe=e(pe)}function sr(e){return e===IDBDatabase.prototype.transaction&&!("objectStoreNames"in IDBTransaction.prototype)?function(t,...n){const r=e.call(X(this),t,...n);return St.set(r,t.sort?t.sort():[t]),A(r)}:nr().includes(e)?function(...t){return e.apply(X(this),t),A(vt.get(this))}:function(...t){return A(e.apply(X(this),t))}}function ar(e){return typeof e=="function"?sr(e):(e instanceof IDBTransaction&&ir(e),er(e,tr())?new Proxy(e,pe):e)}function A(e){if(e instanceof IDBRequest)return rr(e);if(J.has(e))return J.get(e);const t=ar(e);return t!==e&&(J.set(e,t),Ae.set(t,e)),t}const X=e=>Ae.get(e);function cr(e,t,{blocked:n,upgrade:r,blocking:i,terminated:o}={}){const s=indexedDB.open(e,t),a=A(s);return r&&s.addEventListener("upgradeneeded",c=>{r(A(s.result),c.oldVersion,c.newVersion,A(s.transaction),c)}),n&&s.addEventListener("blocked",c=>n(c.oldVersion,c.newVersion,c)),a.then(c=>{o&&c.addEventListener("close",()=>o()),i&&c.addEventListener("versionchange",l=>i(l.oldVersion,l.newVersion,l))}).catch(()=>{}),a}const lr=["get","getKey","getAll","getAllKeys","count"],ur=["put","add","delete","clear"],Z=new Map;function ze(e,t){if(!(e instanceof IDBDatabase&&!(t in e)&&typeof t=="string"))return;if(Z.get(t))return Z.get(t);const n=t.replace(/FromIndex$/,""),r=t!==n,i=ur.includes(n);if(!(n in(r?IDBIndex:IDBObjectStore).prototype)||!(i||lr.includes(n)))return;const o=async function(s,...a){const c=this.transaction(s,i?"readwrite":"readonly");let l=c.store;return r&&(l=l.index(a.shift())),(await Promise.all([l[n](...a),i&&c.done]))[0]};return Z.set(t,o),o}or(e=>({...e,get:(t,n,r)=>ze(t,n)||e.get(t,n,r),has:(t,n)=>!!ze(t,n)||e.has(t,n)}));/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class dr{constructor(t){this.container=t}getPlatformInfoString(){return this.container.getProviders().map(n=>{if(fr(n)){const r=n.getImmediate();return`${r.library}/${r.version}`}else return null}).filter(n=>n).join(" ")}}function fr(e){const t=e.getComponent();return(t==null?void 0:t.type)==="VERSION"}const ge="@firebase/app",Ge="0.9.14";/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const O=new _t("@firebase/app"),hr="@firebase/app-compat",pr="@firebase/analytics-compat",gr="@firebase/analytics",mr="@firebase/app-check-compat",br="@firebase/app-check",wr="@firebase/auth",yr="@firebase/auth-compat",Ir="@firebase/database",Er="@firebase/database-compat",_r="@firebase/functions",vr="@firebase/functions-compat",Sr="@firebase/installations",Ar="@firebase/installations-compat",Tr="@firebase/messaging",Dr="@firebase/messaging-compat",Cr="@firebase/performance",Mr="@firebase/performance-compat",Or="@firebase/remote-config",kr="@firebase/remote-config-compat",Br="@firebase/storage",Rr="@firebase/storage-compat",$r="@firebase/firestore",Pr="@firebase/firestore-compat",Nr="firebase";/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const me="[DEFAULT]",Lr={[ge]:"fire-core",[hr]:"fire-core-compat",[gr]:"fire-analytics",[pr]:"fire-analytics-compat",[br]:"fire-app-check",[mr]:"fire-app-check-compat",[wr]:"fire-auth",[yr]:"fire-auth-compat",[Ir]:"fire-rtdb",[Er]:"fire-rtdb-compat",[_r]:"fire-fn",[vr]:"fire-fn-compat",[Sr]:"fire-iid",[Ar]:"fire-iid-compat",[Tr]:"fire-fcm",[Dr]:"fire-fcm-compat",[Cr]:"fire-perf",[Mr]:"fire-perf-compat",[Or]:"fire-rc",[kr]:"fire-rc-compat",[Br]:"fire-gcs",[Rr]:"fire-gcs-compat",[$r]:"fire-fst",[Pr]:"fire-fst-compat","fire-js":"fire-js",[Nr]:"fire-js-all"};/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const U=new Map,be=new Map;function Fr(e,t){try{e.container.addComponent(t)}catch(n){O.debug(`Component ${t.name} failed to register with FirebaseApp ${e.name}`,n)}}function S(e){const t=e.name;if(be.has(t))return O.debug(`There were multiple attempts to register component ${t}.`),!1;be.set(t,e);for(const n of U.values())Fr(n,e);return!0}function H(e,t){const n=e.container.getProvider("heartbeat").getImmediate({optional:!0});return n&&n.triggerHeartbeat(),e.container.getProvider(t)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const xr={"no-app":"No Firebase App '{$appName}' has been created - call initializeApp() first","bad-app-name":"Illegal App name: '{$appName}","duplicate-app":"Firebase App named '{$appName}' already exists with different options or config","app-deleted":"Firebase App named '{$appName}' already deleted","no-options":"Need to provide options, when not being deployed to hosting via source.","invalid-app-argument":"firebase.{$appName}() takes either no argument or a Firebase App instance.","invalid-log-argument":"First argument to `onLog` must be null or a function.","idb-open":"Error thrown when opening IndexedDB. Original error: {$originalErrorMessage}.","idb-get":"Error thrown when reading from IndexedDB. Original error: {$originalErrorMessage}.","idb-set":"Error thrown when writing to IndexedDB. Original error: {$originalErrorMessage}.","idb-delete":"Error thrown when deleting from IndexedDB. Original error: {$originalErrorMessage}."},T=new x("app","Firebase",xr);/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class jr{constructor(t,n,r){this._isDeleted=!1,this._options=Object.assign({},t),this._config=Object.assign({},n),this._name=n.name,this._automaticDataCollectionEnabled=n.automaticDataCollectionEnabled,this._container=r,this.container.addComponent(new w("app",()=>this,"PUBLIC"))}get automaticDataCollectionEnabled(){return this.checkDestroyed(),this._automaticDataCollectionEnabled}set automaticDataCollectionEnabled(t){this.checkDestroyed(),this._automaticDataCollectionEnabled=t}get name(){return this.checkDestroyed(),this._name}get options(){return this.checkDestroyed(),this._options}get config(){return this.checkDestroyed(),this._config}get container(){return this._container}get isDeleted(){return this._isDeleted}set isDeleted(t){this._isDeleted=t}checkDestroyed(){if(this.isDeleted)throw T.create("app-deleted",{appName:this._name})}}function At(e,t={}){let n=e;typeof t!="object"&&(t={name:t});const r=Object.assign({name:me,automaticDataCollectionEnabled:!1},t),i=r.name;if(typeof i!="string"||!i)throw T.create("bad-app-name",{appName:String(i)});if(n||(n=It()),!n)throw T.create("no-options");const o=U.get(i);if(o){if(V(n,o.options)&&V(r,o.config))return o;throw T.create("duplicate-app",{appName:i})}const s=new Yn(i);for(const c of be.values())s.addComponent(c);const a=new jr(n,r,s);return U.set(i,a),a}function Tt(e=me){const t=U.get(e);if(!t&&e===me&&It())return At();if(!t)throw T.create("no-app",{appName:e});return t}function b(e,t,n){var r;let i=(r=Lr[e])!==null&&r!==void 0?r:e;n&&(i+=`-${n}`);const o=i.match(/\s|\//),s=t.match(/\s|\//);if(o||s){const a=[`Unable to register library "${i}" with version "${t}":`];o&&a.push(`library name "${i}" contains illegal characters (whitespace or "/")`),o&&s&&a.push("and"),s&&a.push(`version name "${t}" contains illegal characters (whitespace or "/")`),O.warn(a.join(" "));return}S(new w(`${i}-version`,()=>({library:i,version:t}),"VERSION"))}/**
 * @license
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Hr="firebase-heartbeat-database",Vr=1,L="firebase-heartbeat-store";let Q=null;function Dt(){return Q||(Q=cr(Hr,Vr,{upgrade:(e,t)=>{switch(t){case 0:e.createObjectStore(L)}}}).catch(e=>{throw T.create("idb-open",{originalErrorMessage:e.message})})),Q}async function Ur(e){try{return await(await Dt()).transaction(L).objectStore(L).get(Ct(e))}catch(t){if(t instanceof $)O.warn(t.message);else{const n=T.create("idb-get",{originalErrorMessage:t==null?void 0:t.message});O.warn(n.message)}}}async function qe(e,t){try{const r=(await Dt()).transaction(L,"readwrite");await r.objectStore(L).put(t,Ct(e)),await r.done}catch(n){if(n instanceof $)O.warn(n.message);else{const r=T.create("idb-set",{originalErrorMessage:n==null?void 0:n.message});O.warn(r.message)}}}function Ct(e){return`${e.name}!${e.options.appId}`}/**
 * @license
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Kr=1024,Wr=30*24*60*60*1e3;class zr{constructor(t){this.container=t,this._heartbeatsCache=null;const n=this.container.getProvider("app").getImmediate();this._storage=new qr(n),this._heartbeatsCachePromise=this._storage.read().then(r=>(this._heartbeatsCache=r,r))}async triggerHeartbeat(){const n=this.container.getProvider("platform-logger").getImmediate().getPlatformInfoString(),r=Ye();if(this._heartbeatsCache===null&&(this._heartbeatsCache=await this._heartbeatsCachePromise),!(this._heartbeatsCache.lastSentHeartbeatDate===r||this._heartbeatsCache.heartbeats.some(i=>i.date===r)))return this._heartbeatsCache.heartbeats.push({date:r,agent:n}),this._heartbeatsCache.heartbeats=this._heartbeatsCache.heartbeats.filter(i=>{const o=new Date(i.date).valueOf();return Date.now()-o<=Wr}),this._storage.overwrite(this._heartbeatsCache)}async getHeartbeatsHeader(){if(this._heartbeatsCache===null&&await this._heartbeatsCachePromise,this._heartbeatsCache===null||this._heartbeatsCache.heartbeats.length===0)return"";const t=Ye(),{heartbeatsToSend:n,unsentEntries:r}=Gr(this._heartbeatsCache.heartbeats),i=yt(JSON.stringify({version:2,heartbeats:n}));return this._heartbeatsCache.lastSentHeartbeatDate=t,r.length>0?(this._heartbeatsCache.heartbeats=r,await this._storage.overwrite(this._heartbeatsCache)):(this._heartbeatsCache.heartbeats=[],this._storage.overwrite(this._heartbeatsCache)),i}}function Ye(){return new Date().toISOString().substring(0,10)}function Gr(e,t=Kr){const n=[];let r=e.slice();for(const i of e){const o=n.find(s=>s.agent===i.agent);if(o){if(o.dates.push(i.date),Je(n)>t){o.dates.pop();break}}else if(n.push({agent:i.agent,dates:[i.date]}),Je(n)>t){n.pop();break}r=r.slice(1)}return{heartbeatsToSend:n,unsentEntries:r}}class qr{constructor(t){this.app=t,this._canUseIndexedDBPromise=this.runIndexedDBEnvironmentCheck()}async runIndexedDBEnvironmentCheck(){return ve()?Se().then(()=>!0).catch(()=>!1):!1}async read(){return await this._canUseIndexedDBPromise?await Ur(this.app)||{heartbeats:[]}:{heartbeats:[]}}async overwrite(t){var n;if(await this._canUseIndexedDBPromise){const i=await this.read();return qe(this.app,{lastSentHeartbeatDate:(n=t.lastSentHeartbeatDate)!==null&&n!==void 0?n:i.lastSentHeartbeatDate,heartbeats:t.heartbeats})}else return}async add(t){var n;if(await this._canUseIndexedDBPromise){const i=await this.read();return qe(this.app,{lastSentHeartbeatDate:(n=t.lastSentHeartbeatDate)!==null&&n!==void 0?n:i.lastSentHeartbeatDate,heartbeats:[...i.heartbeats,...t.heartbeats]})}else return}}function Je(e){return yt(JSON.stringify({version:2,heartbeats:e})).length}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Yr(e){S(new w("platform-logger",t=>new dr(t),"PRIVATE")),S(new w("heartbeat",t=>new zr(t),"PRIVATE")),b(ge,Ge,e),b(ge,Ge,"esm2017"),b("fire-js","")}Yr("");var Jr="firebase",Xr="10.0.0";/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */b(Jr,Xr,"app");const Zr=(e,t)=>t.some(n=>e instanceof n);let Xe,Ze;function Qr(){return Xe||(Xe=[IDBDatabase,IDBObjectStore,IDBIndex,IDBCursor,IDBTransaction])}function ei(){return Ze||(Ze=[IDBCursor.prototype.advance,IDBCursor.prototype.continue,IDBCursor.prototype.continuePrimaryKey])}const Mt=new WeakMap,we=new WeakMap,Ot=new WeakMap,ee=new WeakMap,Te=new WeakMap;function ti(e){const t=new Promise((n,r)=>{const i=()=>{e.removeEventListener("success",o),e.removeEventListener("error",s)},o=()=>{n(D(e.result)),i()},s=()=>{r(e.error),i()};e.addEventListener("success",o),e.addEventListener("error",s)});return t.then(n=>{n instanceof IDBCursor&&Mt.set(n,e)}).catch(()=>{}),Te.set(t,e),t}function ni(e){if(we.has(e))return;const t=new Promise((n,r)=>{const i=()=>{e.removeEventListener("complete",o),e.removeEventListener("error",s),e.removeEventListener("abort",s)},o=()=>{n(),i()},s=()=>{r(e.error||new DOMException("AbortError","AbortError")),i()};e.addEventListener("complete",o),e.addEventListener("error",s),e.addEventListener("abort",s)});we.set(e,t)}let ye={get(e,t,n){if(e instanceof IDBTransaction){if(t==="done")return we.get(e);if(t==="objectStoreNames")return e.objectStoreNames||Ot.get(e);if(t==="store")return n.objectStoreNames[1]?void 0:n.objectStore(n.objectStoreNames[0])}return D(e[t])},set(e,t,n){return e[t]=n,!0},has(e,t){return e instanceof IDBTransaction&&(t==="done"||t==="store")?!0:t in e}};function ri(e){ye=e(ye)}function ii(e){return e===IDBDatabase.prototype.transaction&&!("objectStoreNames"in IDBTransaction.prototype)?function(t,...n){const r=e.call(te(this),t,...n);return Ot.set(r,t.sort?t.sort():[t]),D(r)}:ei().includes(e)?function(...t){return e.apply(te(this),t),D(Mt.get(this))}:function(...t){return D(e.apply(te(this),t))}}function oi(e){return typeof e=="function"?ii(e):(e instanceof IDBTransaction&&ni(e),Zr(e,Qr())?new Proxy(e,ye):e)}function D(e){if(e instanceof IDBRequest)return ti(e);if(ee.has(e))return ee.get(e);const t=oi(e);return t!==e&&(ee.set(e,t),Te.set(t,e)),t}const te=e=>Te.get(e);function si(e,t,{blocked:n,upgrade:r,blocking:i,terminated:o}={}){const s=indexedDB.open(e,t),a=D(s);return r&&s.addEventListener("upgradeneeded",c=>{r(D(s.result),c.oldVersion,c.newVersion,D(s.transaction))}),n&&s.addEventListener("blocked",()=>n()),a.then(c=>{o&&c.addEventListener("close",()=>o()),i&&c.addEventListener("versionchange",()=>i())}).catch(()=>{}),a}const ai=["get","getKey","getAll","getAllKeys","count"],ci=["put","add","delete","clear"],ne=new Map;function Qe(e,t){if(!(e instanceof IDBDatabase&&!(t in e)&&typeof t=="string"))return;if(ne.get(t))return ne.get(t);const n=t.replace(/FromIndex$/,""),r=t!==n,i=ci.includes(n);if(!(n in(r?IDBIndex:IDBObjectStore).prototype)||!(i||ai.includes(n)))return;const o=async function(s,...a){const c=this.transaction(s,i?"readwrite":"readonly");let l=c.store;return r&&(l=l.index(a.shift())),(await Promise.all([l[n](...a),i&&c.done]))[0]};return ne.set(t,o),o}ri(e=>({...e,get:(t,n,r)=>Qe(t,n)||e.get(t,n,r),has:(t,n)=>!!Qe(t,n)||e.has(t,n)}));const kt="@firebase/installations",De="0.6.4";/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Bt=1e4,Rt=`w:${De}`,$t="FIS_v2",li="https://firebaseinstallations.googleapis.com/v1",ui=60*60*1e3,di="installations",fi="Installations";/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const hi={"missing-app-config-values":'Missing App configuration value: "{$valueName}"',"not-registered":"Firebase Installation is not registered.","installation-not-found":"Firebase Installation not found.","request-failed":'{$requestName} request failed with error "{$serverCode} {$serverStatus}: {$serverMessage}"',"app-offline":"Could not process request. Application offline.","delete-pending-registration":"Can't delete installation while there is a pending registration request."},k=new x(di,fi,hi);function Pt(e){return e instanceof $&&e.code.includes("request-failed")}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Nt({projectId:e}){return`${li}/projects/${e}/installations`}function Lt(e){return{token:e.token,requestStatus:2,expiresIn:gi(e.expiresIn),creationTime:Date.now()}}async function Ft(e,t){const r=(await t.json()).error;return k.create("request-failed",{requestName:e,serverCode:r.code,serverMessage:r.message,serverStatus:r.status})}function xt({apiKey:e}){return new Headers({"Content-Type":"application/json",Accept:"application/json","x-goog-api-key":e})}function pi(e,{refreshToken:t}){const n=xt(e);return n.append("Authorization",mi(t)),n}async function jt(e){const t=await e();return t.status>=500&&t.status<600?e():t}function gi(e){return Number(e.replace("s","000"))}function mi(e){return`${$t} ${e}`}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function bi({appConfig:e,heartbeatServiceProvider:t},{fid:n}){const r=Nt(e),i=xt(e),o=t.getImmediate({optional:!0});if(o){const l=await o.getHeartbeatsHeader();l&&i.append("x-firebase-client",l)}const s={fid:n,authVersion:$t,appId:e.appId,sdkVersion:Rt},a={method:"POST",headers:i,body:JSON.stringify(s)},c=await jt(()=>fetch(r,a));if(c.ok){const l=await c.json();return{fid:l.fid||n,registrationStatus:2,refreshToken:l.refreshToken,authToken:Lt(l.authToken)}}else throw await Ft("Create Installation",c)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Ht(e){return new Promise(t=>{setTimeout(t,e)})}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function wi(e){return btoa(String.fromCharCode(...e)).replace(/\+/g,"-").replace(/\//g,"_")}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const yi=/^[cdef][\w-]{21}$/,Ie="";function Ii(){try{const e=new Uint8Array(17);(self.crypto||self.msCrypto).getRandomValues(e),e[0]=112+e[0]%16;const n=Ei(e);return yi.test(n)?n:Ie}catch{return Ie}}function Ei(e){return wi(e).substr(0,22)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function G(e){return`${e.appName}!${e.appId}`}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Vt=new Map;function Ut(e,t){const n=G(e);Kt(n,t),_i(n,t)}function Kt(e,t){const n=Vt.get(e);if(n)for(const r of n)r(t)}function _i(e,t){const n=vi();n&&n.postMessage({key:e,fid:t}),Si()}let M=null;function vi(){return!M&&"BroadcastChannel"in self&&(M=new BroadcastChannel("[Firebase] FID Change"),M.onmessage=e=>{Kt(e.data.key,e.data.fid)}),M}function Si(){Vt.size===0&&M&&(M.close(),M=null)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Ai="firebase-installations-database",Ti=1,B="firebase-installations-store";let re=null;function Ce(){return re||(re=si(Ai,Ti,{upgrade:(e,t)=>{switch(t){case 0:e.createObjectStore(B)}}})),re}async function K(e,t){const n=G(e),i=(await Ce()).transaction(B,"readwrite"),o=i.objectStore(B),s=await o.get(n);return await o.put(t,n),await i.done,(!s||s.fid!==t.fid)&&Ut(e,t.fid),t}async function Wt(e){const t=G(e),r=(await Ce()).transaction(B,"readwrite");await r.objectStore(B).delete(t),await r.done}async function q(e,t){const n=G(e),i=(await Ce()).transaction(B,"readwrite"),o=i.objectStore(B),s=await o.get(n),a=t(s);return a===void 0?await o.delete(n):await o.put(a,n),await i.done,a&&(!s||s.fid!==a.fid)&&Ut(e,a.fid),a}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function Me(e){let t;const n=await q(e.appConfig,r=>{const i=Di(r),o=Ci(e,i);return t=o.registrationPromise,o.installationEntry});return n.fid===Ie?{installationEntry:await t}:{installationEntry:n,registrationPromise:t}}function Di(e){const t=e||{fid:Ii(),registrationStatus:0};return zt(t)}function Ci(e,t){if(t.registrationStatus===0){if(!navigator.onLine){const i=Promise.reject(k.create("app-offline"));return{installationEntry:t,registrationPromise:i}}const n={fid:t.fid,registrationStatus:1,registrationTime:Date.now()},r=Mi(e,n);return{installationEntry:n,registrationPromise:r}}else return t.registrationStatus===1?{installationEntry:t,registrationPromise:Oi(e)}:{installationEntry:t}}async function Mi(e,t){try{const n=await bi(e,t);return K(e.appConfig,n)}catch(n){throw Pt(n)&&n.customData.serverCode===409?await Wt(e.appConfig):await K(e.appConfig,{fid:t.fid,registrationStatus:0}),n}}async function Oi(e){let t=await et(e.appConfig);for(;t.registrationStatus===1;)await Ht(100),t=await et(e.appConfig);if(t.registrationStatus===0){const{installationEntry:n,registrationPromise:r}=await Me(e);return r||n}return t}function et(e){return q(e,t=>{if(!t)throw k.create("installation-not-found");return zt(t)})}function zt(e){return ki(e)?{fid:e.fid,registrationStatus:0}:e}function ki(e){return e.registrationStatus===1&&e.registrationTime+Bt<Date.now()}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function Bi({appConfig:e,heartbeatServiceProvider:t},n){const r=Ri(e,n),i=pi(e,n),o=t.getImmediate({optional:!0});if(o){const l=await o.getHeartbeatsHeader();l&&i.append("x-firebase-client",l)}const s={installation:{sdkVersion:Rt,appId:e.appId}},a={method:"POST",headers:i,body:JSON.stringify(s)},c=await jt(()=>fetch(r,a));if(c.ok){const l=await c.json();return Lt(l)}else throw await Ft("Generate Auth Token",c)}function Ri(e,{fid:t}){return`${Nt(e)}/${t}/authTokens:generate`}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function Oe(e,t=!1){let n;const r=await q(e.appConfig,o=>{if(!Gt(o))throw k.create("not-registered");const s=o.authToken;if(!t&&Ni(s))return o;if(s.requestStatus===1)return n=$i(e,t),o;{if(!navigator.onLine)throw k.create("app-offline");const a=Fi(o);return n=Pi(e,a),a}});return n?await n:r.authToken}async function $i(e,t){let n=await tt(e.appConfig);for(;n.authToken.requestStatus===1;)await Ht(100),n=await tt(e.appConfig);const r=n.authToken;return r.requestStatus===0?Oe(e,t):r}function tt(e){return q(e,t=>{if(!Gt(t))throw k.create("not-registered");const n=t.authToken;return xi(n)?Object.assign(Object.assign({},t),{authToken:{requestStatus:0}}):t})}async function Pi(e,t){try{const n=await Bi(e,t),r=Object.assign(Object.assign({},t),{authToken:n});return await K(e.appConfig,r),n}catch(n){if(Pt(n)&&(n.customData.serverCode===401||n.customData.serverCode===404))await Wt(e.appConfig);else{const r=Object.assign(Object.assign({},t),{authToken:{requestStatus:0}});await K(e.appConfig,r)}throw n}}function Gt(e){return e!==void 0&&e.registrationStatus===2}function Ni(e){return e.requestStatus===2&&!Li(e)}function Li(e){const t=Date.now();return t<e.creationTime||e.creationTime+e.expiresIn<t+ui}function Fi(e){const t={requestStatus:1,requestTime:Date.now()};return Object.assign(Object.assign({},e),{authToken:t})}function xi(e){return e.requestStatus===1&&e.requestTime+Bt<Date.now()}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function ji(e){const t=e,{installationEntry:n,registrationPromise:r}=await Me(t);return r?r.catch(console.error):Oe(t).catch(console.error),n.fid}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function Hi(e,t=!1){const n=e;return await Vi(n),(await Oe(n,t)).token}async function Vi(e){const{registrationPromise:t}=await Me(e);t&&await t}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Ui(e){if(!e||!e.options)throw ie("App Configuration");if(!e.name)throw ie("App Name");const t=["projectId","apiKey","appId"];for(const n of t)if(!e.options[n])throw ie(n);return{appName:e.name,projectId:e.options.projectId,apiKey:e.options.apiKey,appId:e.options.appId}}function ie(e){return k.create("missing-app-config-values",{valueName:e})}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const qt="installations",Ki="installations-internal",Wi=e=>{const t=e.getProvider("app").getImmediate(),n=Ui(t),r=H(t,"heartbeat");return{app:t,appConfig:n,heartbeatServiceProvider:r,_delete:()=>Promise.resolve()}},zi=e=>{const t=e.getProvider("app").getImmediate(),n=H(t,qt).getImmediate();return{getId:()=>ji(n),getToken:i=>Hi(n,i)}};function Gi(){S(new w(qt,Wi,"PUBLIC")),S(new w(Ki,zi,"PRIVATE"))}Gi();b(kt,De);b(kt,De,"esm2017");const qi=(e,t)=>t.some(n=>e instanceof n);let nt,rt;function Yi(){return nt||(nt=[IDBDatabase,IDBObjectStore,IDBIndex,IDBCursor,IDBTransaction])}function Ji(){return rt||(rt=[IDBCursor.prototype.advance,IDBCursor.prototype.continue,IDBCursor.prototype.continuePrimaryKey])}const Yt=new WeakMap,Ee=new WeakMap,Jt=new WeakMap,oe=new WeakMap,ke=new WeakMap;function Xi(e){const t=new Promise((n,r)=>{const i=()=>{e.removeEventListener("success",o),e.removeEventListener("error",s)},o=()=>{n(v(e.result)),i()},s=()=>{r(e.error),i()};e.addEventListener("success",o),e.addEventListener("error",s)});return t.then(n=>{n instanceof IDBCursor&&Yt.set(n,e)}).catch(()=>{}),ke.set(t,e),t}function Zi(e){if(Ee.has(e))return;const t=new Promise((n,r)=>{const i=()=>{e.removeEventListener("complete",o),e.removeEventListener("error",s),e.removeEventListener("abort",s)},o=()=>{n(),i()},s=()=>{r(e.error||new DOMException("AbortError","AbortError")),i()};e.addEventListener("complete",o),e.addEventListener("error",s),e.addEventListener("abort",s)});Ee.set(e,t)}let _e={get(e,t,n){if(e instanceof IDBTransaction){if(t==="done")return Ee.get(e);if(t==="objectStoreNames")return e.objectStoreNames||Jt.get(e);if(t==="store")return n.objectStoreNames[1]?void 0:n.objectStore(n.objectStoreNames[0])}return v(e[t])},set(e,t,n){return e[t]=n,!0},has(e,t){return e instanceof IDBTransaction&&(t==="done"||t==="store")?!0:t in e}};function Qi(e){_e=e(_e)}function eo(e){return e===IDBDatabase.prototype.transaction&&!("objectStoreNames"in IDBTransaction.prototype)?function(t,...n){const r=e.call(se(this),t,...n);return Jt.set(r,t.sort?t.sort():[t]),v(r)}:Ji().includes(e)?function(...t){return e.apply(se(this),t),v(Yt.get(this))}:function(...t){return v(e.apply(se(this),t))}}function to(e){return typeof e=="function"?eo(e):(e instanceof IDBTransaction&&Zi(e),qi(e,Yi())?new Proxy(e,_e):e)}function v(e){if(e instanceof IDBRequest)return Xi(e);if(oe.has(e))return oe.get(e);const t=to(e);return t!==e&&(oe.set(e,t),ke.set(t,e)),t}const se=e=>ke.get(e);function Xt(e,t,{blocked:n,upgrade:r,blocking:i,terminated:o}={}){const s=indexedDB.open(e,t),a=v(s);return r&&s.addEventListener("upgradeneeded",c=>{r(v(s.result),c.oldVersion,c.newVersion,v(s.transaction))}),n&&s.addEventListener("blocked",()=>n()),a.then(c=>{o&&c.addEventListener("close",()=>o()),i&&c.addEventListener("versionchange",()=>i())}).catch(()=>{}),a}function ae(e,{blocked:t}={}){const n=indexedDB.deleteDatabase(e);return t&&n.addEventListener("blocked",()=>t()),v(n).then(()=>{})}const no=["get","getKey","getAll","getAllKeys","count"],ro=["put","add","delete","clear"],ce=new Map;function it(e,t){if(!(e instanceof IDBDatabase&&!(t in e)&&typeof t=="string"))return;if(ce.get(t))return ce.get(t);const n=t.replace(/FromIndex$/,""),r=t!==n,i=ro.includes(n);if(!(n in(r?IDBIndex:IDBObjectStore).prototype)||!(i||no.includes(n)))return;const o=async function(s,...a){const c=this.transaction(s,i?"readwrite":"readonly");let l=c.store;return r&&(l=l.index(a.shift())),(await Promise.all([l[n](...a),i&&c.done]))[0]};return ce.set(t,o),o}Qi(e=>({...e,get:(t,n,r)=>it(t,n)||e.get(t,n,r),has:(t,n)=>!!it(t,n)||e.has(t,n)}));/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const io="/firebase-messaging-sw.js",oo="/firebase-cloud-messaging-push-scope",Zt="BDOU99-h67HcA6JeFXHbSNMu7e2yNNu3RzoMj8TM4W88jITfq7ZmPvIM1Iv-4_l2LxQcYwhqby2xGpWwzjfAnG4",so="https://fcmregistrations.googleapis.com/v1",Qt="google.c.a.c_id",ao="google.c.a.c_l",co="google.c.a.ts",lo="google.c.a.e";var ot;(function(e){e[e.DATA_MESSAGE=1]="DATA_MESSAGE",e[e.DISPLAY_NOTIFICATION=3]="DISPLAY_NOTIFICATION"})(ot||(ot={}));/**
 * @license
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except
 * in compliance with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under the License
 * is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
 * or implied. See the License for the specific language governing permissions and limitations under
 * the License.
 */var F;(function(e){e.PUSH_RECEIVED="push-received",e.NOTIFICATION_CLICKED="notification-clicked"})(F||(F={}));/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function _(e){const t=new Uint8Array(e);return btoa(String.fromCharCode(...t)).replace(/=/g,"").replace(/\+/g,"-").replace(/\//g,"_")}function uo(e){const t="=".repeat((4-e.length%4)%4),n=(e+t).replace(/\-/g,"+").replace(/_/g,"/"),r=atob(n),i=new Uint8Array(r.length);for(let o=0;o<r.length;++o)i[o]=r.charCodeAt(o);return i}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const le="fcm_token_details_db",fo=5,st="fcm_token_object_Store";async function ho(e){if("databases"in indexedDB&&!(await indexedDB.databases()).map(o=>o.name).includes(le))return null;let t=null;return(await Xt(le,fo,{upgrade:async(r,i,o,s)=>{var a;if(i<2||!r.objectStoreNames.contains(st))return;const c=s.objectStore(st),l=await c.index("fcmSenderId").get(e);if(await c.clear(),!!l){if(i===2){const u=l;if(!u.auth||!u.p256dh||!u.endpoint)return;t={token:u.fcmToken,createTime:(a=u.createTime)!==null&&a!==void 0?a:Date.now(),subscriptionOptions:{auth:u.auth,p256dh:u.p256dh,endpoint:u.endpoint,swScope:u.swScope,vapidKey:typeof u.vapidKey=="string"?u.vapidKey:_(u.vapidKey)}}}else if(i===3){const u=l;t={token:u.fcmToken,createTime:u.createTime,subscriptionOptions:{auth:_(u.auth),p256dh:_(u.p256dh),endpoint:u.endpoint,swScope:u.swScope,vapidKey:_(u.vapidKey)}}}else if(i===4){const u=l;t={token:u.fcmToken,createTime:u.createTime,subscriptionOptions:{auth:_(u.auth),p256dh:_(u.p256dh),endpoint:u.endpoint,swScope:u.swScope,vapidKey:_(u.vapidKey)}}}}}})).close(),await ae(le),await ae("fcm_vapid_details_db"),await ae("undefined"),po(t)?t:null}function po(e){if(!e||!e.subscriptionOptions)return!1;const{subscriptionOptions:t}=e;return typeof e.createTime=="number"&&e.createTime>0&&typeof e.token=="string"&&e.token.length>0&&typeof t.auth=="string"&&t.auth.length>0&&typeof t.p256dh=="string"&&t.p256dh.length>0&&typeof t.endpoint=="string"&&t.endpoint.length>0&&typeof t.swScope=="string"&&t.swScope.length>0&&typeof t.vapidKey=="string"&&t.vapidKey.length>0}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const go="firebase-messaging-database",mo=1,R="firebase-messaging-store";let ue=null;function Be(){return ue||(ue=Xt(go,mo,{upgrade:(e,t)=>{switch(t){case 0:e.createObjectStore(R)}}})),ue}async function en(e){const t=$e(e),r=await(await Be()).transaction(R).objectStore(R).get(t);if(r)return r;{const i=await ho(e.appConfig.senderId);if(i)return await Re(e,i),i}}async function Re(e,t){const n=$e(e),i=(await Be()).transaction(R,"readwrite");return await i.objectStore(R).put(t,n),await i.done,t}async function bo(e){const t=$e(e),r=(await Be()).transaction(R,"readwrite");await r.objectStore(R).delete(t),await r.done}function $e({appConfig:e}){return e.appId}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const wo={"missing-app-config-values":'Missing App configuration value: "{$valueName}"',"only-available-in-window":"This method is available in a Window context.","only-available-in-sw":"This method is available in a service worker context.","permission-default":"The notification permission was not granted and dismissed instead.","permission-blocked":"The notification permission was not granted and blocked instead.","unsupported-browser":"This browser doesn't support the API's required to use the Firebase SDK.","indexed-db-unsupported":"This browser doesn't support indexedDb.open() (ex. Safari iFrame, Firefox Private Browsing, etc)","failed-service-worker-registration":"We are unable to register the default service worker. {$browserErrorMessage}","token-subscribe-failed":"A problem occurred while subscribing the user to FCM: {$errorInfo}","token-subscribe-no-token":"FCM returned no token when subscribing the user to push.","token-unsubscribe-failed":"A problem occurred while unsubscribing the user from FCM: {$errorInfo}","token-update-failed":"A problem occurred while updating the user from FCM: {$errorInfo}","token-update-no-token":"FCM returned no token when updating the user to push.","use-sw-after-get-token":"The useServiceWorker() method may only be called once and must be called before calling getToken() to ensure your service worker is used.","invalid-sw-registration":"The input to useServiceWorker() must be a ServiceWorkerRegistration.","invalid-bg-handler":"The input to setBackgroundMessageHandler() must be a function.","invalid-vapid-key":"The public VAPID key must be a string.","use-vapid-key-after-get-token":"The usePublicVapidKey() method may only be called once and must be called before calling getToken() to ensure your VAPID key is used."},f=new x("messaging","Messaging",wo);/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function yo(e,t){const n=await Ne(e),r=nn(t),i={method:"POST",headers:n,body:JSON.stringify(r)};let o;try{o=await(await fetch(Pe(e.appConfig),i)).json()}catch(s){throw f.create("token-subscribe-failed",{errorInfo:s==null?void 0:s.toString()})}if(o.error){const s=o.error.message;throw f.create("token-subscribe-failed",{errorInfo:s})}if(!o.token)throw f.create("token-subscribe-no-token");return o.token}async function Io(e,t){const n=await Ne(e),r=nn(t.subscriptionOptions),i={method:"PATCH",headers:n,body:JSON.stringify(r)};let o;try{o=await(await fetch(`${Pe(e.appConfig)}/${t.token}`,i)).json()}catch(s){throw f.create("token-update-failed",{errorInfo:s==null?void 0:s.toString()})}if(o.error){const s=o.error.message;throw f.create("token-update-failed",{errorInfo:s})}if(!o.token)throw f.create("token-update-no-token");return o.token}async function tn(e,t){const r={method:"DELETE",headers:await Ne(e)};try{const o=await(await fetch(`${Pe(e.appConfig)}/${t}`,r)).json();if(o.error){const s=o.error.message;throw f.create("token-unsubscribe-failed",{errorInfo:s})}}catch(i){throw f.create("token-unsubscribe-failed",{errorInfo:i==null?void 0:i.toString()})}}function Pe({projectId:e}){return`${so}/projects/${e}/registrations`}async function Ne({appConfig:e,installations:t}){const n=await t.getToken();return new Headers({"Content-Type":"application/json",Accept:"application/json","x-goog-api-key":e.apiKey,"x-goog-firebase-installations-auth":`FIS ${n}`})}function nn({p256dh:e,auth:t,endpoint:n,vapidKey:r}){const i={web:{endpoint:n,auth:t,p256dh:e}};return r!==Zt&&(i.web.applicationPubKey=r),i}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Eo=7*24*60*60*1e3;async function _o(e){const t=await Ao(e.swRegistration,e.vapidKey),n={vapidKey:e.vapidKey,swScope:e.swRegistration.scope,endpoint:t.endpoint,auth:_(t.getKey("auth")),p256dh:_(t.getKey("p256dh"))},r=await en(e.firebaseDependencies);if(r){if(To(r.subscriptionOptions,n))return Date.now()>=r.createTime+Eo?So(e,{token:r.token,createTime:Date.now(),subscriptionOptions:n}):r.token;try{await tn(e.firebaseDependencies,r.token)}catch(i){console.warn(i)}return at(e.firebaseDependencies,n)}else return at(e.firebaseDependencies,n)}async function vo(e){const t=await en(e.firebaseDependencies);t&&(await tn(e.firebaseDependencies,t.token),await bo(e.firebaseDependencies));const n=await e.swRegistration.pushManager.getSubscription();return n?n.unsubscribe():!0}async function So(e,t){try{const n=await Io(e.firebaseDependencies,t),r=Object.assign(Object.assign({},t),{token:n,createTime:Date.now()});return await Re(e.firebaseDependencies,r),n}catch(n){throw await vo(e),n}}async function at(e,t){const r={token:await yo(e,t),createTime:Date.now(),subscriptionOptions:t};return await Re(e,r),r.token}async function Ao(e,t){const n=await e.pushManager.getSubscription();return n||e.pushManager.subscribe({userVisibleOnly:!0,applicationServerKey:uo(t)})}function To(e,t){const n=t.vapidKey===e.vapidKey,r=t.endpoint===e.endpoint,i=t.auth===e.auth,o=t.p256dh===e.p256dh;return n&&r&&i&&o}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function ct(e){const t={from:e.from,collapseKey:e.collapse_key,messageId:e.fcmMessageId};return Do(t,e),Co(t,e),Mo(t,e),t}function Do(e,t){if(!t.notification)return;e.notification={};const n=t.notification.title;n&&(e.notification.title=n);const r=t.notification.body;r&&(e.notification.body=r);const i=t.notification.image;i&&(e.notification.image=i);const o=t.notification.icon;o&&(e.notification.icon=o)}function Co(e,t){t.data&&(e.data=t.data)}function Mo(e,t){var n,r,i,o,s;if(!t.fcmOptions&&!(!((n=t.notification)===null||n===void 0)&&n.click_action))return;e.fcmOptions={};const a=(i=(r=t.fcmOptions)===null||r===void 0?void 0:r.link)!==null&&i!==void 0?i:(o=t.notification)===null||o===void 0?void 0:o.click_action;a&&(e.fcmOptions.link=a);const c=(s=t.fcmOptions)===null||s===void 0?void 0:s.analytics_label;c&&(e.fcmOptions.analyticsLabel=c)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Oo(e){return typeof e=="object"&&!!e&&Qt in e}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */rn("hts/frbslgigp.ogepscmv/ieo/eaylg","tp:/ieaeogn-agolai.o/1frlglgc/o");rn("AzSCbw63g1R0nCw85jG8","Iaya3yLKwmgvh7cF0q4");function rn(e,t){const n=[];for(let r=0;r<e.length;r++)n.push(e.charAt(r)),r<t.length&&n.push(t.charAt(r));return n.join("")}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function ko(e){if(!e||!e.options)throw de("App Configuration Object");if(!e.name)throw de("App Name");const t=["projectId","apiKey","appId","messagingSenderId"],{options:n}=e;for(const r of t)if(!n[r])throw de(r);return{appName:e.name,projectId:n.projectId,apiKey:n.apiKey,appId:n.appId,senderId:n.messagingSenderId}}function de(e){return f.create("missing-app-config-values",{valueName:e})}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Bo{constructor(t,n,r){this.deliveryMetricsExportedToBigQueryEnabled=!1,this.onBackgroundMessageHandler=null,this.onMessageHandler=null,this.logEvents=[],this.isLogServiceStarted=!1;const i=ko(t);this.firebaseDependencies={app:t,appConfig:i,installations:n,analyticsProvider:r}}_delete(){return Promise.resolve()}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function Ro(e){try{e.swRegistration=await navigator.serviceWorker.register(io,{scope:oo}),e.swRegistration.update().catch(()=>{})}catch(t){throw f.create("failed-service-worker-registration",{browserErrorMessage:t==null?void 0:t.message})}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function $o(e,t){if(!t&&!e.swRegistration&&await Ro(e),!(!t&&e.swRegistration)){if(!(t instanceof ServiceWorkerRegistration))throw f.create("invalid-sw-registration");e.swRegistration=t}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function Po(e,t){t?e.vapidKey=t:e.vapidKey||(e.vapidKey=Zt)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function on(e,t){if(!navigator)throw f.create("only-available-in-window");if(Notification.permission==="default"&&await Notification.requestPermission(),Notification.permission!=="granted")throw f.create("permission-blocked");return await Po(e,t==null?void 0:t.vapidKey),await $o(e,t==null?void 0:t.serviceWorkerRegistration),_o(e)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function No(e,t,n){const r=Lo(t);(await e.firebaseDependencies.analyticsProvider.get()).logEvent(r,{message_id:n[Qt],message_name:n[ao],message_time:n[co],message_device_time:Math.floor(Date.now()/1e3)})}function Lo(e){switch(e){case F.NOTIFICATION_CLICKED:return"notification_open";case F.PUSH_RECEIVED:return"notification_foreground";default:throw new Error}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function Fo(e,t){const n=t.data;if(!n.isFirebaseMessaging)return;e.onMessageHandler&&n.messageType===F.PUSH_RECEIVED&&(typeof e.onMessageHandler=="function"?e.onMessageHandler(ct(n)):e.onMessageHandler.next(ct(n)));const r=n.data;Oo(r)&&r[lo]==="1"&&await No(e,n.messageType,r)}const lt="@firebase/messaging",ut="0.12.4";/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const xo=e=>{const t=new Bo(e.getProvider("app").getImmediate(),e.getProvider("installations-internal").getImmediate(),e.getProvider("analytics-internal"));return navigator.serviceWorker.addEventListener("message",n=>Fo(t,n)),t},jo=e=>{const t=e.getProvider("messaging").getImmediate();return{getToken:r=>on(t,r)}};function Ho(){S(new w("messaging",xo,"PUBLIC")),S(new w("messaging-internal",jo,"PRIVATE")),b(lt,ut),b(lt,ut,"esm2017")}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function Vo(){try{await Se()}catch{return!1}return typeof window<"u"&&ve()&&Et()&&"serviceWorker"in navigator&&"PushManager"in window&&"Notification"in window&&"fetch"in window&&ServiceWorkerRegistration.prototype.hasOwnProperty("showNotification")&&PushSubscription.prototype.hasOwnProperty("getKey")}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Uo(e,t){if(!navigator)throw f.create("only-available-in-window");return e.onMessageHandler=t,()=>{e.onMessageHandler=null}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Ko(e=Tt()){return Vo().then(t=>{if(!t)throw f.create("unsupported-browser")},t=>{throw f.create("indexed-db-unsupported")}),H(j(e),"messaging").getImmediate()}async function Wo(e,t){return e=j(e),on(e,t)}function zo(e,t){return e=j(e),Uo(e,t)}Ho();/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const W="analytics",Go="firebase_id",qo="origin",Yo=60*1e3,Jo="https://firebase.googleapis.com/v1alpha/projects/-/apps/{app-id}/webConfig",Le="https://www.googletagmanager.com/gtag/js";/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const h=new _t("@firebase/analytics");/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Xo={"already-exists":"A Firebase Analytics instance with the appId {$id}  already exists. Only one Firebase Analytics instance can be created for each appId.","already-initialized":"initializeAnalytics() cannot be called again with different options than those it was initially called with. It can be called again with the same options to return the existing instance, or getAnalytics() can be used to get a reference to the already-intialized instance.","already-initialized-settings":"Firebase Analytics has already been initialized.settings() must be called before initializing any Analytics instanceor it will have no effect.","interop-component-reg-failed":"Firebase Analytics Interop Component failed to instantiate: {$reason}","invalid-analytics-context":"Firebase Analytics is not supported in this environment. Wrap initialization of analytics in analytics.isSupported() to prevent initialization in unsupported environments. Details: {$errorInfo}","indexeddb-unavailable":"IndexedDB unavailable or restricted in this environment. Wrap initialization of analytics in analytics.isSupported() to prevent initialization in unsupported environments. Details: {$errorInfo}","fetch-throttle":"The config fetch request timed out while in an exponential backoff state. Unix timestamp in milliseconds when fetch request throttling ends: {$throttleEndTimeMillis}.","config-fetch-failed":"Dynamic config fetch failed: [{$httpStatus}] {$responseMessage}","no-api-key":'The "apiKey" field is empty in the local Firebase config. Firebase Analytics requires this field tocontain a valid API key.',"no-app-id":'The "appId" field is empty in the local Firebase config. Firebase Analytics requires this field tocontain a valid app ID.',"no-client-id":'The "client_id" field is empty.',"invalid-gtag-resource":"Trusted Types detected an invalid gtag resource: {$gtagURL}."},m=new x("analytics","Analytics",Xo);/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Zo(e){if(!e.startsWith(Le)){const t=m.create("invalid-gtag-resource",{gtagURL:e});return h.warn(t.message),""}return e}function sn(e){return Promise.all(e.map(t=>t.catch(n=>n)))}function Qo(e,t){let n;return window.trustedTypes&&(n=window.trustedTypes.createPolicy(e,t)),n}function es(e,t){const n=Qo("firebase-js-sdk-policy",{createScriptURL:Zo}),r=document.createElement("script"),i=`${Le}?l=${e}&id=${t}`;r.src=n?n==null?void 0:n.createScriptURL(i):i,r.async=!0,document.head.appendChild(r)}function ts(e){let t=[];return Array.isArray(window[e])?t=window[e]:window[e]=t,t}async function ns(e,t,n,r,i,o){const s=r[i];try{if(s)await t[s];else{const c=(await sn(n)).find(l=>l.measurementId===i);c&&await t[c.appId]}}catch(a){h.error(a)}e("config",i,o)}async function rs(e,t,n,r,i){try{let o=[];if(i&&i.send_to){let s=i.send_to;Array.isArray(s)||(s=[s]);const a=await sn(n);for(const c of s){const l=a.find(p=>p.measurementId===c),u=l&&t[l.appId];if(u)o.push(u);else{o=[];break}}}o.length===0&&(o=Object.values(t)),await Promise.all(o),e("event",r,i||{})}catch(o){h.error(o)}}function is(e,t,n,r){async function i(o,...s){try{if(o==="event"){const[a,c]=s;await rs(e,t,n,a,c)}else if(o==="config"){const[a,c]=s;await ns(e,t,n,r,a,c)}else if(o==="consent"){const[a]=s;e("consent","update",a)}else if(o==="get"){const[a,c,l]=s;e("get",a,c,l)}else if(o==="set"){const[a]=s;e("set",a)}else e(o,...s)}catch(a){h.error(a)}}return i}function os(e,t,n,r,i){let o=function(...s){window[r].push(arguments)};return window[i]&&typeof window[i]=="function"&&(o=window[i]),window[i]=is(o,e,t,n),{gtagCore:o,wrappedGtag:window[i]}}function ss(e){const t=window.document.getElementsByTagName("script");for(const n of Object.values(t))if(n.src&&n.src.includes(Le)&&n.src.includes(e))return n;return null}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const as=30,cs=1e3;class ls{constructor(t={},n=cs){this.throttleMetadata=t,this.intervalMillis=n}getThrottleMetadata(t){return this.throttleMetadata[t]}setThrottleMetadata(t,n){this.throttleMetadata[t]=n}deleteThrottleMetadata(t){delete this.throttleMetadata[t]}}const an=new ls;function us(e){return new Headers({Accept:"application/json","x-goog-api-key":e})}async function ds(e){var t;const{appId:n,apiKey:r}=e,i={method:"GET",headers:us(r)},o=Jo.replace("{app-id}",n),s=await fetch(o,i);if(s.status!==200&&s.status!==304){let a="";try{const c=await s.json();!((t=c.error)===null||t===void 0)&&t.message&&(a=c.error.message)}catch{}throw m.create("config-fetch-failed",{httpStatus:s.status,responseMessage:a})}return s.json()}async function fs(e,t=an,n){const{appId:r,apiKey:i,measurementId:o}=e.options;if(!r)throw m.create("no-app-id");if(!i){if(o)return{measurementId:o,appId:r};throw m.create("no-api-key")}const s=t.getThrottleMetadata(r)||{backoffCount:0,throttleEndTimeMillis:Date.now()},a=new gs;return setTimeout(async()=>{a.abort()},n!==void 0?n:Yo),cn({appId:r,apiKey:i,measurementId:o},s,a,t)}async function cn(e,{throttleEndTimeMillis:t,backoffCount:n},r,i=an){var o;const{appId:s,measurementId:a}=e;try{await hs(r,t)}catch(c){if(a)return h.warn(`Timed out fetching this Firebase app's measurement ID from the server. Falling back to the measurement ID ${a} provided in the "measurementId" field in the local Firebase config. [${c==null?void 0:c.message}]`),{appId:s,measurementId:a};throw c}try{const c=await ds(e);return i.deleteThrottleMetadata(s),c}catch(c){const l=c;if(!ps(l)){if(i.deleteThrottleMetadata(s),a)return h.warn(`Failed to fetch this Firebase app's measurement ID from the server. Falling back to the measurement ID ${a} provided in the "measurementId" field in the local Firebase config. [${l==null?void 0:l.message}]`),{appId:s,measurementId:a};throw c}const u=Number((o=l==null?void 0:l.customData)===null||o===void 0?void 0:o.httpStatus)===503?Ue(n,i.intervalMillis,as):Ue(n,i.intervalMillis),p={throttleEndTimeMillis:Date.now()+u,backoffCount:n+1};return i.setThrottleMetadata(s,p),h.debug(`Calling attemptFetch again in ${u} millis`),cn(e,p,r,i)}}function hs(e,t){return new Promise((n,r)=>{const i=Math.max(t-Date.now(),0),o=setTimeout(n,i);e.addEventListener(()=>{clearTimeout(o),r(m.create("fetch-throttle",{throttleEndTimeMillis:t}))})})}function ps(e){if(!(e instanceof $)||!e.customData)return!1;const t=Number(e.customData.httpStatus);return t===429||t===500||t===503||t===504}class gs{constructor(){this.listeners=[]}addEventListener(t){this.listeners.push(t)}abort(){this.listeners.forEach(t=>t())}}async function ms(e,t,n,r,i){if(i&&i.global){e("event",n,r);return}else{const o=await t,s=Object.assign(Object.assign({},r),{send_to:o});e("event",n,s)}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function bs(){if(ve())try{await Se()}catch(e){return h.warn(m.create("indexeddb-unavailable",{errorInfo:e==null?void 0:e.toString()}).message),!1}else return h.warn(m.create("indexeddb-unavailable",{errorInfo:"IndexedDB is not available in this environment."}).message),!1;return!0}async function ws(e,t,n,r,i,o,s){var a;const c=fs(e);c.then(g=>{n[g.measurementId]=g.appId,e.options.measurementId&&g.measurementId!==e.options.measurementId&&h.warn(`The measurement ID in the local Firebase config (${e.options.measurementId}) does not match the measurement ID fetched from the server (${g.measurementId}). To ensure analytics events are always sent to the correct Analytics property, update the measurement ID field in the local config or remove it from the local config.`)}).catch(g=>h.error(g)),t.push(c);const l=bs().then(g=>{if(g)return r.getId()}),[u,p]=await Promise.all([c,l]);ss(o)||es(o,u.measurementId),i("js",new Date);const y=(a=s==null?void 0:s.config)!==null&&a!==void 0?a:{};return y[qo]="firebase",y.update=!0,p!=null&&(y[Go]=p),i("config",u.measurementId,y),u.measurementId}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class ys{constructor(t){this.app=t}_delete(){return delete N[this.app.options.appId],Promise.resolve()}}let N={},dt=[];const ft={};let fe="dataLayer",Is="gtag",ht,ln,pt=!1;function Es(){const e=[];if(Fn()&&e.push("This is a browser extension environment."),Et()||e.push("Cookies are not available."),e.length>0){const t=e.map((r,i)=>`(${i+1}) ${r}`).join(" "),n=m.create("invalid-analytics-context",{errorInfo:t});h.warn(n.message)}}function _s(e,t,n){Es();const r=e.options.appId;if(!r)throw m.create("no-app-id");if(!e.options.apiKey)if(e.options.measurementId)h.warn(`The "apiKey" field is empty in the local Firebase config. This is needed to fetch the latest measurement ID for this Firebase app. Falling back to the measurement ID ${e.options.measurementId} provided in the "measurementId" field in the local Firebase config.`);else throw m.create("no-api-key");if(N[r]!=null)throw m.create("already-exists",{id:r});if(!pt){ts(fe);const{wrappedGtag:o,gtagCore:s}=os(N,dt,ft,fe,Is);ln=o,ht=s,pt=!0}return N[r]=ws(e,dt,ft,t,ht,fe,n),new ys(e)}function vs(e=Tt()){e=j(e);const t=H(e,W);return t.isInitialized()?t.getImmediate():Ss(e)}function Ss(e,t={}){const n=H(e,W);if(n.isInitialized()){const i=n.getImmediate();if(V(t,n.getOptions()))return i;throw m.create("already-initialized")}return n.initialize({options:t})}function As(e,t,n,r){e=j(e),ms(ln,N[e.app.options.appId],t,n,r).catch(i=>h.error(i))}const gt="@firebase/analytics",mt="0.10.0";function Ts(){S(new w(W,(t,{options:n})=>{const r=t.getProvider("app").getImmediate(),i=t.getProvider("installations-internal").getImmediate();return _s(r,i,n)},"PUBLIC")),S(new w("analytics-internal",e,"PRIVATE")),b(gt,mt),b(gt,mt,"esm2017");function e(t){try{const n=t.getProvider(W).getImmediate();return{logEvent:(r,i,o)=>As(n,r,i,o)}}catch(n){throw m.create("interop-component-reg-failed",{reason:n})}}}Ts();const E={FIREBASE_API_KEY:"AIzaSyDP1Cgfm4JdWWe6-9En9tk436ZUU8g7Bso",FIREBASE_PROJECT_ID:"appzen-367e1",FIREBASE_APP_ID:"1:505825563328:web:2eafa477f23bdbc2cd1325",FIREBASE_SENDER_ID:"505825563328",PUSH_PK:{}.VITE_PUSH_PK,MEASUREMENT_ID:{}.VITE_MEASUREMENT_ID,GOOGLE_APP_KEY:"appzen-367e1",GOOGLE_APP_CLIENT:"505825563328-878d7el2iokoipm7jp060tqvmp4u88o3.apps.googleusercontent.com",TEMPO_API:"https://api.tempo.io/4/",TEMPO_TOKEN:"yWBJVcaKCHhc17Pf1pT9zgCFF2VU73",FIREBASE_VAPID_KEY:"BBBLswSfpUHAbpLg87uRxFu8GNNb6RUckeVpOG8es-erlML3W14TBX9_F1WxIL-XYL3U8GVkYhmmEcqIDt3EHtc"},Ds={apiKey:E.FIREBASE_API_KEY,authDomain:`${E.FIREBASE_PROJECT_ID}.firebaseapp.com`,databaseURL:`https://${E.FIREBASE_PROJECT_ID}.firebaseio.com`,projectId:E.FIREBASE_PROJECT_ID,storageBucket:`${E.FIREBASE_PROJECT_ID}.appspot.com`,messagingSenderId:E.FIREBASE_SENDER_ID,measurementId:E.MEASUREMENT_ID,appId:E.FIREBASE_APP_ID},Fe=At(Ds),z=Ko(Fe);vs(Fe);function Cs(){console.log("Requesting permission..."),Notification.requestPermission().then(e=>{e==="granted"&&console.log("Notification permission granted.")})}const js=e=>(Wo(z,{vapidKey:E.FIREBASE_VAPID_KEY}).then(t=>{t?e&&e(t):Cs()}).catch(t=>{console.log("An error occurred while retrieving token. ",t)}),z),Hs=e=>{console.log("Running",z,Fe),zo(z,t=>{console.log(t),e&&e(t)})};export{Ns as D,Tn as I,Y as M,Rs as a,Bs as b,Ps as c,Hs as d,Ls as e,$s as g,Fs as m,xs as p,js as u};

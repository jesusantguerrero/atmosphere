import{J as k}from"./Button.ae5a3d7e.js";import{J as I}from"./FormSection.77327c48.js";import{J as F}from"./Input.d29a71cc.js";import{J as V}from"./InputError.925da517.js";import{_ as j}from"./Label.dcf66d06.js";import{J as A}from"./ActionMessage.458182d9.js";import{J as B}from"./SecondaryButton.292dcb3c.js";import{A as C}from"./atmosphere-ui.es.c3e31e0f.js";import{_ as N}from"./LogerInput.65190e93.js";import{l as r,o as p,g as h,b as s,c as U,d as a,a as o,w as _,j as P,K as x,m as v,e as g,n as L,i as m}from"./app.ed3e752b.js";import{_ as M}from"./_plugin-vue_export-helper.cdc0426e.js";import"./SectionTitle.ecbc1ca4.js";const z={components:{JetActionMessage:A,JetButton:k,JetFormSection:I,JetInput:F,JetInputError:V,JetLabel:j,JetSecondaryButton:B,AtField:C,LogerInput:N},props:["user"],data(){return{form:this.$inertia.form({_method:"PUT",name:this.user.name,email:this.user.email,photo:null}),photoPreview:null}},methods:{updateProfileInformation(){this.$refs.photo&&(this.form.photo=this.$refs.photo.files[0]),this.form.post(route("user-profile-information.update"),{errorBag:"updateProfileInformation",preserveScroll:!0,onSuccess:()=>this.clearPhotoFileInput()})},selectNewPhoto(){this.$refs.photo.click()},updatePhotoPreview(){const n=new FileReader;n.onload=t=>{this.photoPreview=t.target.result},n.readAsDataURL(this.$refs.photo.files[0])},deletePhoto(){this.$inertia.delete(route("current-user-photo.destroy"),{preserveScroll:!0,onSuccess:()=>{this.photoPreview=null,this.clearPhotoFileInput()}})},clearPhotoFileInput(){var n;(n=this.$refs.photo)!=null&&n.value&&(this.$refs.photo.value=null)}}},E={key:0,class:"col-span-6 sm:col-span-4"},R={class:"mt-2"},D=["src","alt"],T={class:"mt-2"},K=m(" Select A New Photo "),q=m(" Remove Photo "),G=m(" Saved. "),H=m(" Save ");function O(n,t,c,Q,e,l){const b=r("jet-label"),u=r("jet-secondary-button"),w=r("jet-input-error"),f=r("LogerInput"),d=r("AtField"),J=r("JetActionMessage"),y=r("JetButton"),S=r("JetFormSection");return p(),h(S,{title:"Profile Information",description:"Update your account's profile information and email address.",onSubmitted:l.updateProfileInformation},{form:s(()=>[n.$page.props.jetstream.managesProfilePhotos?(p(),U("div",E,[a("input",{type:"file",class:"hidden",ref:"photo",onChange:t[0]||(t[0]=(...i)=>l.updatePhotoPreview&&l.updatePhotoPreview(...i))},null,544),o(b,{for:"photo",value:"Photo"}),_(a("div",R,[a("img",{src:c.user.profile_photo_url,alt:c.user.name,class:"rounded-full h-20 w-20 object-cover"},null,8,D)],512),[[P,!e.photoPreview]]),_(a("div",T,[a("span",{class:"block rounded-full w-20 h-20",style:x("background-size: cover; background-repeat: no-repeat; background-position: center center; background-image: url('"+e.photoPreview+"');")},null,4)],512),[[P,e.photoPreview]]),o(u,{class:"mt-2 mr-2",type:"button",onClick:v(l.selectNewPhoto,["prevent"])},{default:s(()=>[K]),_:1},8,["onClick"]),c.user.profile_photo_path?(p(),h(u,{key:0,type:"button",class:"mt-2",onClick:v(l.deletePhoto,["prevent"])},{default:s(()=>[q]),_:1},8,["onClick"])):g("",!0),o(w,{message:e.form.errors.photo,class:"mt-2"},null,8,["message"])])):g("",!0),o(d,{class:"col-span-6 sm:col-span-4",label:"Name",field:"name",errors:e.form.errors},{default:s(()=>[o(f,{id:"name",type:"text",modelValue:e.form.name,"onUpdate:modelValue":t[1]||(t[1]=i=>e.form.name=i),autocomplete:"name"},null,8,["modelValue"])]),_:1},8,["errors"]),o(d,{class:"col-span-6 sm:col-span-4",label:"Email",field:"email",errors:e.form.errors},{default:s(()=>[o(f,{id:"email",type:"email",modelValue:e.form.email,"onUpdate:modelValue":t[2]||(t[2]=i=>e.form.email=i)},null,8,["modelValue"])]),_:1},8,["errors"])]),actions:s(()=>[o(J,{on:e.form.recentlySuccessful,class:"mr-3"},{default:s(()=>[G]),_:1},8,["on"]),o(y,{class:L({"opacity-25":e.form.processing}),disabled:e.form.processing},{default:s(()=>[H]),_:1},8,["class","disabled"])]),_:1},8,["onSubmitted"])}const ie=M(z,[["render",O]]);export{ie as default};
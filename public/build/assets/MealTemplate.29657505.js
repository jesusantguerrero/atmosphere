import{_}from"./SectionNav.164c7b94.js";import{o as s,g as p,b as c,k as i,a5 as u,c as t,d as e,a as m,F as d,h as f,t as n,f as h,e as x,i as b}from"./app.d8e74fa3.js";import{_ as y}from"./SectionTitle.5007996d.js";/* empty css                                              */import"./naiveui.0ad5ca37.js";const C={__name:"MealSectionNav",setup(l){return(a,o)=>(s(),p(_,{sections:[{label:"Planner",url:"/meal-planner"},{label:"Recipes",url:"/meals"},{label:"Ingredients",url:"/ingredients"},{label:"Menus",url:"/meals/menus"}]},{actions:c(()=>[i(a.$slots,"actions")]),_:3}))}},g={class:"relative px-8 pt-16 pb-20 mx-auto max-w-screen-2xl"},w={key:0,class:""},k={class:"flex w-full justify-between mb-2"},M=b(" Meals "),B={class:"flex space-x-4"},N={class:"capitalize"},$={class:"w-full mt-4"},F={__name:"MealTemplate",props:{title:{type:String},showMealTypes:{type:Boolean,default:!0}},setup(l){const a=u().props;return(o,v)=>(s(),t("section",g,[l.showMealTypes?(s(),t("header",w,[e("article",k,[m(y,null,{default:c(()=>[M]),_:1})]),e("article",B,[(s(!0),t(d,null,f(h(a).mealTypes,r=>(s(),t("div",{key:r.id,class:"cursor-pointer font-bold text-white border-primary transition rounded-md bg-primary/80 h-20 w-full flex flex-col items-center justify-center"},[e("h4",N,n(r.name),1),e("p",null,n(r.description),1)]))),128))])])):x("",!0),e("div",$,[i(o.$slots,"default")])]))}};export{C as _,F as a};
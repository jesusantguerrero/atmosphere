import{f as b}from"./formatMoney.a91b934f.js";import{D as k,o as s,c as l,d as o,F as u,h as f,n as d,t as r,k as y,e as h,K as g,f as p,g as w}from"./app.cefd7668.js";const x={props:{col:{type:Object},data:{type:Object}},setup(t,{slots:m}){return()=>{var n;return k("div",(n=t.col)==null?void 0:n.render(t.data))}}},C=["data"],$={class:"px-2 py-4 font-bold text-left text-body border-b border-gray-200"},v={key:0},B={key:0},D=["colspan"],S={key:3},T={key:1},A={key:0},j=["colspan"],E=["colspan"],N={class:"py-5 text-center text-base-200 w-full"},O=["colspan"],L={__name:"CustomTable",props:{cols:{type:Array,required:!0},isLoading:{type:Boolean,default:!1},tableData:{type:Array},config:{type:Object,default(){return{}}},showPrepend:{type:Boolean,default:!1},showAppend:{type:Boolean,default:!1},emptyText:{type:String,default:"No data found"},hideEmptyText:{type:Boolean,default:!1}},setup(t){const m=({row:n})=>n.headerClass;return(n,i)=>(s(),l("table",{class:"table-fixed",style:{width:"100%"},data:t.tableData,"header-cell-class-name":m,onSortChange:i[0]||(i[0]=a=>n.$emit("sort",a)),onRowClick:i[1]||(i[1]=a=>n.$emit("row-click",a))},[o("thead",null,[o("tr",$,[(s(!0),l(u,null,f(t.cols,a=>(s(),l("th",{key:a.name,class:d(["px-2 py-4",[a.headerClass]])},r(a.label),3))),128))])]),t.tableData.length?(s(),l("tbody",v,[t.showPrepend?(s(),l("tr",B,[o("td",{colspan:t.cols.length},[y(n.$slots,"prepend")],8,D)])):h("",!0),(s(!0),l(u,null,f(t.tableData,(a,c)=>(s(),l("tr",{key:`data-row-${c}`,class:d(["text-body",{"bg-base-lvl-2":c%2}])},[(s(!0),l(u,null,f(t.cols,e=>(s(),l("td",{key:e.name,class:"h-full align-baseline",style:g({width:e.width,maxWidth:e.maxWidth})},[o("div",{class:d(["flex flex-col w-full h-full px-2 py-1 text-left",e.class])},[y(n.$slots,e.name,{scope:{row:a,value:a[e.name],col:e,field:e.name,$index:c}},()=>[e.type=="calc"?(s(),l("div",{key:0,class:d(e.class)},r(e.formula(a)),3)):e.type=="money"?(s(),l("div",{key:1,class:d(e.class)},r(p(b)(a[e.name])),3)):e.render?(s(),w(p(x),{key:2,class:d(e.class),col:e,data:a},null,8,["class","col","data"])):(s(),l("div",S,r(a[e.name]),1))])],2)],4))),128))],2))),128))])):(s(),l("tbody",T,[t.showAppend?(s(),l("tr",A,[o("td",{colspan:t.cols.length},[y(n.$slots,"append")],8,j)])):h("",!0),o("tr",null,[o("td",{colspan:t.cols.length,class:"flex flex-col w-full"},[t.hideEmptyText?h("",!0):y(n.$slots,"empty",{key:0},()=>[o("div",N,r(t.emptyText),1)])],8,E)])])),o("tfoot",null,[o("tr",null,[o("td",{colspan:t.cols.length},[y(n.$slots,"append")],8,O)])])],40,C))}};export{L as _};
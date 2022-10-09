const n=(r,e="DOP")=>{try{return new Intl.NumberFormat("en-US",{style:"currency",currency:e,currencyDisplay:"symbol"}).format(Number(r)||0)}catch{return r}};export{n as f};

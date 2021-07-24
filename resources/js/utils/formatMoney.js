const formatMoney = (value) => {
    return new Intl.NumberFormat("en-IN", {
      style: "currency",
      currency: "DOP",
      currencyDisplay: "symbol"
    }).format(value || 0);
}
export default formatMoney;

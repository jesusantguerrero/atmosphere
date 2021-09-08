const formatMoney = (value, symbol = "DOP") => {
    return new Intl.NumberFormat("en-IN", {
      style: "currency",
      currency: symbol,
      currencyDisplay: "symbol"
    }).format(value || 0);
}
export default formatMoney;

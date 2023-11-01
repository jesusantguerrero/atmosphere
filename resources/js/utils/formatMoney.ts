export const formatMoney = (value, symbol = null) => {
    const defaultSymbol = symbol ?? window?.logerAppSettings?.currency_code ?? 'DOP';
    try {
        return new Intl.NumberFormat("en-US", {
          style: "currency",
          currency: defaultSymbol,
          currencyDisplay: "symbol"
        }).format(Number(value) || 0);
    } catch (err) {
        return value;
    }
}

export default formatMoney;

export const formatMoney = (value, symbol = null) => {
    const defaultSymbol = symbol ?? window?.logerAppSettings?.currency_code ?? 'DOP';
    try {
        const formatter = new Intl.NumberFormat("en-US", {
          style: "currency",
          currency: defaultSymbol,
          currencyDisplay: "symbol"
        });
        const amount = Number(value) || 0;
        const precision = formatter.resolvedOptions().maximumFractionDigits;
        return formatter.format(Math.abs(amount) < 0.5 * 10 ** -precision ? 0 : amount);
    } catch (err) {
        return value;
    }
}

export default formatMoney;

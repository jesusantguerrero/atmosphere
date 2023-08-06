export * from "./formatters"
export * from "./tableCols"
export * from "./tableAccountCols"
export * from "./useTransactionModal"

export const TRANSACTION_DIRECTIONS = {
    WITHDRAW: 'WITHDRAW',
    DEPOSIT: 'DEPOSIT',
    TRANSFER: 'TRANSFER'
}

export const getVariances = (current = 0, last = 0) => {
    if (last === 0) {
      return 0;
    }
    const variance = ((current - last) / last) * 100;
    return Number.isNaN(variance) ? 0 : variance.toFixed(2);
};

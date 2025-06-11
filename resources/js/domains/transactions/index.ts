import { router } from "@inertiajs/vue3"
import { ITransaction } from "./models"
import { useTransactionStore } from "@/store/transactions"


export * from "./formatters"
export * from "./tableCols"
export * from "./tableAccountCols"
export * from "./tableCategoryCols"
export * from "./reconciliationCols"
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



export const removeTransaction = (transaction: ITransaction, only: string[] = []) => {
    if (confirm(`Are you sure you want to remove this transaction? ${JSON.stringify(transaction)}`)) {
        router.delete(`/transactions/${transaction.id}`, {
            preserveScroll: true,
            preserveState: true,
            onSuccess() {
                router.reload({
                    only,
                    preserveScroll: true,
                    preserveState: true,
                });

                const transactionStore = useTransactionStore();
                transactionStore.emitTransaction(transaction as ITransaction, 'delete', transaction);
            }
        })
    }
}

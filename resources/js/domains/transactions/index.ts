import { router } from "@inertiajs/vue3"
import axios from "axios"
import { IAccount, ITransaction } from "./models"
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

/**
 * Canonical credit-card predicate. The account wizard requires both a credit
 * limit and a closing day, so either field identifies a card — but only
 * `credit_limit` drives the available-credit rendering in AccountItem, so it
 * is the one we key off everywhere.
 */
export const isCreditCard = (account: Pick<IAccount, 'credit_limit'>) => {
    return Number(account?.credit_limit ?? 0) > 0;
};

export const saveAccountsReorder = (items: IAccount[]) => {
    const accounts = items?.reduce((savedItems, account) => {
        savedItems[account.id] = account;
        return savedItems;
    }, {} as Record<number, IAccount>);

    return axios.patch('/api/accounts/', { accounts });
};

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

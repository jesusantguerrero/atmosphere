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

type Numeric = number | string | null | undefined;

/**
 * Percent change between two periods, as a fixed-2 string.
 *
 * Returns `null` when there is nothing to compare against — a zero, null or
 * non-numeric baseline. That is not the same as 0%: a month that went from
 * nothing to something has no meaningful percentage, and rendering it as 0%
 * understates the change. Callers must render `null` as an em dash.
 *
 * The old strict `last === 0` check let two real cases through, because MySQL
 * hands these numbers over as strings and SUM() over an empty period is NULL:
 * `"0.00" === 0` and `null === 0` are both false, so both fell into the
 * division and produced Infinity.
 */
export const getVariances = (current: Numeric = 0, last: Numeric = 0): string | null => {
    const currentValue = Number(current ?? 0);
    const lastValue = Number(last ?? 0);

    if (!Number.isFinite(currentValue) || !Number.isFinite(lastValue) || lastValue === 0) {
        return null;
    }

    const variance = ((currentValue - lastValue) / lastValue) * 100;

    return Number.isFinite(variance) ? variance.toFixed(2) : null;
};

/** Display form of {@link getVariances} — an em dash when not comparable. */
export const formatVariance = (variance: string | null, suffix = '%'): string => {
    return variance === null ? '—' : `${variance}${suffix}`;
};



export const removeTransaction = (transaction: ITransaction, only: string[] = []) => {
    const label = (transaction as any)?.description || (transaction as any)?.payee_name || (transaction as any)?.title;
    if (confirm(label ? `Remove "${label}"? This can't be undone.` : `Remove this transaction? This can't be undone.`)) {
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

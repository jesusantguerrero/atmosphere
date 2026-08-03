import { defineStore } from "pinia";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { TRANSACTION_DIRECTIONS } from "@/domains/transactions";
import { format } from "date-fns";
import { ITransaction } from "@/domains/transactions/models";

interface QuickTransactionData {
    accountName: string;
    categoryName?: string;
    amount: number;
    date: string;
    counterAccountName?: string
}
export const useTransactionStore = defineStore('transactions', () => {
    // Monotonic signal bumped whenever a transaction is created/updated/deleted.
    // Store-backed views that derive from transactions but aren't refreshed by
    // Inertia's partial reload (e.g. the budget side widget's summary) watch
    // this and re-pull. Keeps 'No spending history yet' from going stale after
    // the first logged expense.
    const revision = ref(0);
    const notifyChanged = () => { revision.value++; };

    const onSubmit = (data: QuickTransactionData, direction = TRANSACTION_DIRECTIONS.WITHDRAW) => {
        return new Promise((resolve, reject) => useForm({
            resource_type_id: "MANUAL",
            total: data.amount,
            date: format(new Date(data.date), "yyyy-MM-dd"),
            status: "verified",
            direction: data.counterAccountName ? TRANSACTION_DIRECTIONS.WITHDRAW : direction,
            categoryName: data.counterAccountName ? null : data.categoryName,
            accountName: data.accountName,
            counterAccountName: data.counterAccountName ?? null
        }).post(route("transactions.quickstore"), {
            onBefore(evt) {
              if (!evt.data.total) {
                alert("The balance should be more than 0");
              }
            },
            onSuccess: () => {
              notifyChanged();
              resolve(true)
            },
            onError() {
                reject()
            }
        }));
    };

    const emitTransaction = (transaction: ITransaction, method: string, oldData?: ITransaction) => {
        notifyChanged();
    }

    const reload = () => {

    }

    return {
       onSubmit,
       emitTransaction,
       notifyChanged,
       revision,
       reload
    }
})

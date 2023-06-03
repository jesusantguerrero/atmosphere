import { defineStore } from "pinia";
import { useForm } from "@inertiajs/vue3";
import { TRANSACTION_DIRECTIONS } from "@/domains/transactions";
import { format } from "date-fns";

interface QuickTransactionData {
    accountName: string;
    categoryName?: string;
    amount: number;
    date: string;
    counterAccountName?: string 
}
export const useTransactionStore = defineStore('transactions', () => {
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
              resolve(true)
            },
            onError() {
                reject()
            }
          }));
      };

    return {
       onSubmit
    }
})

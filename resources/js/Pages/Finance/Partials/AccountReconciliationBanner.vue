<script setup lang="ts">
import { router, useForm} from "@inertiajs/vue3";
import { computed } from "vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";

// import { IServerSearchData, useServerSearch } from "@/composables/useServerSearch";

import { formatMoney } from "@/utils";
import { IAccount } from "@/domains/transactions/models";
import AccountReconciliationAlert from "@/domains/transactions/components/AccountReconciliationAlert.vue";


const { account } = defineProps<{
    account: IAccount;
}>();

const goToReconciliation = () => {
    router.visit(`/finance/reconciliation/${account?.reconciliation_last?.id}`)
}

const isMatched = computed(() => {
    return account.balance == account.reconciliation_last?.amount;
})

const adjustmentForm = useForm({
    date: account.reconciliation_last?.date,
    balance: account.balance,
})
const adjustAndFinish = () => {
    if (!isMatched.value) {
        adjustmentForm.put(`/finance/reconciliation/${account?.reconciliation_last?.id}/save-adjustment`)
    } else {
        adjustmentForm
    .transform((data) => ({
      ...data,
      date: account.reconciliation_last?.date,
    }))
    .put(`/finance/reconciliation/${account.reconciliation_last?.id}`, {
      onFinish() {
        adjustmentForm.reset();
      },
    });
    }
}

const differenceStateText = computed(() => {
    return (account.balance || 0) > (account.reconciliation_last?.amount ?? 0) ? 'higher' : 'lower'
})

const differenceAmount = computed(() => {
    return Math.abs(account.reconciliation_last?.amount ?? 0 - account.balance)
})

const hasPendingReconciliation = computed(() => {
    return account.reconciliation_last?.status  == 'pending';
})

</script>

<template>
    <section class="flex items-center justify-between w-full gap-3 px-4 py-3 border rounded-md border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800"
        v-if="hasPendingReconciliation"
    >
        <article class="flex items-center gap-2 text-sm text-amber-800 dark:text-amber-200">
            <AccountReconciliationAlert class="shrink-0" />
            <p v-if="!isMatched">
                Difference of
                <strong>{{ formatMoney(differenceAmount) }}</strong>
                — Loger balance is {{ differenceStateText }} than your bank statement.
            </p>
            <p v-else>
                Reconciliation is balanced and ready to complete.
            </p>
        </article>
        <article class="flex items-center gap-2 shrink-0">
            <button
                v-if="!isMatched"
                @click="goToReconciliation()"
                :disabled="adjustmentForm.processing"
                class="px-3 py-1.5 text-xs font-medium rounded-md bg-amber-200 text-amber-900 hover:bg-amber-300 transition-colors dark:bg-amber-800 dark:text-amber-100 dark:hover:bg-amber-700"
            >
                Review
            </button>
            <button
                @click="adjustAndFinish()"
                :disabled="adjustmentForm.processing"
                class="px-3 py-1.5 text-xs font-medium rounded-md bg-amber-600 text-white hover:bg-amber-700 transition-colors"
            >
                Save adjustment and finish
            </button>
        </article>
    </section>
</template>

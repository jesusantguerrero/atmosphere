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
    router.visit(`/finance/reconciliation/${account?.reconciliations_pending.id}`)
}

const isMatched = computed(() => {
    return account.balance == account.reconciliations_pending?.amount;
})
const adjustmentForm = useForm({
    date: account.reconciliations_pending?.date,
    balance: account.balance,
})
const adjustAndFinish = () => {
    if (!isMatched.value) {
        adjustmentForm.put(`/finance/reconciliation/${account?.reconciliations_pending.id}/save-adjustment`)
    } else {
        adjustmentForm
    .transform((data) => ({
      ...data,
      date: account.reconciliations_pending?.date,
    }))
    .put(`/finance/reconciliation/${account.reconciliations_pending.id}`, {
      onFinish() {
        adjustmentForm.reset();
      },
    });
    }
}

const differenceStateText = computed(() => {

    return (account.reconciliations_pending?.difference || 0) < (account.reconciliations_pending?.amount ?? 0) ? 'higher' : 'lower'
})

</script>

<template>
        <section class="flex justify-between w-full px-4 py-2 text-white cursor-pointer bg-secondary"
            v-if="account.reconciliations_pending"
        >
        <article class="flex items-center">
            <AccountReconciliationAlert class="mr-2 text-white" />
            <p v-if="!isMatched">
                This account's cleared balance in
                <strong>Loger</strong> is {{ formatMoney(account.reconciliations_pending.difference) }} {{ differenceStateText }} than your
                <strong>
                    bank account.
                </strong>
            </p>
        </article>
        <article class="flex space-x-2">
            <LogerButton
                variant="secondary"
                v-if="!isMatched"
                @click="goToReconciliation()"
                :disabled="adjustmentForm.processing"
                :processing="adjustmentForm.processing"
            >
                Review
            </LogerButton>
            <LogerButton variant="secondary"
                @click="adjustAndFinish()"
                :disabled="adjustmentForm.processing"
                :processing="adjustmentForm.processing"
            >
                <template #icon>
                    <IMdiCheck />
                </template>
                Save adjustment and finish
            </LogerButton>
        </article>
    </section>
</template>

<script setup lang="ts">
import { router, useForm} from "@inertiajs/vue3";

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

const adjustmentForm = useForm({})
const adjustAndFinish = () => {
    adjustmentForm.put(`/finance/reconciliation/${account?.reconciliations_pending.id}/save-adjustment`)
}

</script>

<template>
        <section class="flex justify-between w-full px-4 py-2 text-white cursor-pointer bg-secondary" 
            v-if="account.reconciliations_pending"    
        >   
        <article class="flex items-center">
            <AccountReconciliationAlert class="mr-2 text-white" />
            <p>
                This account's cleared balance in 
                <strong>Loger</strong> is {{ formatMoney(account.reconciliations_pending.difference) }} lower than your 
                <strong>
                    bank account.
                </strong>
            </p>
        </article>
        <article class="flex space-x-2">
            <LogerButton 
                variant="secondary"
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
                Save adjustment and finish
            </LogerButton>
        </article>
    </section>
</template>

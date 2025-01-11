<script lang="ts" setup>
import { computed } from 'vue';
import AccountsLedger from '@/domains/transactions/components/AccountsLedger.vue';
import CreditCardsLedger from '@/domains/transactions/components/CreditCardsLedger.vue';
import { IAccount } from '@/domains/transactions/models';

const props = defineProps<{
    accounts: IAccount[];
}>()


const creditCards = computed(() => {
    return props.accounts.filter((account) => account.credit_closing_day)
    .toSorted((a, b) => a.credit_closing_day - b.credit_closing_day)
    .toReversed()
})
const bankOrCash = computed(() => {
    return props.accounts.filter((account) => !account.credit_closing_day)
})
</script>

<template>
<main class="space-y-4">
    <CreditCardsLedger
        :accounts="creditCards"
        :class="[]"
        class="rounded-b-md md:rounded-md min-h-min bg-base-lvl-3 mt-2"
    />
    <section>
        <AccountsLedger
            :accounts="bankOrCash"
            :class="[]"
            class="px-4 py-2 space-y-4 cursor-pointer md:mt-8 rounded-b-md md:rounded-md min-h-min bg-base-lvl-3"
        />
    </section>
</main>
</template>

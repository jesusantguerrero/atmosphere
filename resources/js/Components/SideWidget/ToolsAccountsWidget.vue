<script lang="ts" setup>
import { computed } from 'vue';
import AccountsLedger from '@/domains/transactions/components/AccountsLedger.vue';
import { IAccount } from '@/domains/transactions/models';

const props = defineProps<{
    accounts: IAccount[];
}>()

const bankOrCash = computed(() => {
    return props.accounts.filter((account) => !account.credit_closing_day)
})
</script>

<template>
<main class="space-y-4">
    <AccountsLedger
        :accounts="bankOrCash"
        :class="[]"
        class="px-4 py-2 space-y-4 cursor-pointer rounded-b-md md:rounded-md min-h-min bg-base-lvl-3"
    />
</main>
</template>

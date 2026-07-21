<script lang="ts" setup>
import AccountsLedger from '@/domains/transactions/components/AccountsLedger.vue';
import { saveAccountsReorder } from '@/domains/transactions';
import { IAccount } from '@/domains/transactions/models';

defineProps<{
    accounts: IAccount[];
}>()
</script>

<template>
<main class="space-y-4">
    <AccountsLedger
        :accounts="accounts"
        class="px-4 py-2 space-y-4 cursor-pointer rounded-b-md md:rounded-md min-h-min bg-base-lvl-3"
        @reordered="saveAccountsReorder"
    />
</main>
</template>

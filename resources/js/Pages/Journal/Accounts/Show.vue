<template>
<AppLayout>
    <TransactionSearch
        :transactions="account.transactions"
        :server-search-options="{}"
        title="Transactions of account"
    />
</AppLayout>
</template>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue'
    import TransactionSearch from '@/Components/templates/TransactionSearch.vue';

   defineProps({
        account: {
            type: Object,
            default: () => ({})
        },
        serverSearchOptions: {
            type: Object,
            default: () => ({})
        },
})
</script>

<template>
<AppLayout>
    <FinanceTemplate title="Transactions" :accounts="accounts">
        <TransactionTemplate
            :transactions="transactions"
            :server-search-options="serverSearchOptions"
        />
    </FinanceTemplate>
</AppLayout>
</template>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue'
    import TransactionTemplate from '@/Components/templates/TransactionTemplate.vue';
    import FinanceTemplate from '@/Components/templates/FinanceTemplate.vue';

    defineProps({
        transactions: {
            type: Array,
            default: () => []
        },
        accounts: {
            type: Array,
            default: () => []
        },
        serverSearchOptions: {
            type: Object,
            default: () => ({})
        },
    })
</script>

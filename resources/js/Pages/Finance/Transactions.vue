<template>
<AppLayout>
    <FinanceTemplate title="Transactions" :accounts="accounts">
        <TransactionSearch
            :transactions="transactions"
            :server-search-options="serverSearchOptions"
        />
    </FinanceTemplate>
</AppLayout>
</template>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue'
    import TransactionSearch from '../../Components/templates/TransactionSearch.vue';
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

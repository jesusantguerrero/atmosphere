<template>
<AppLayout>
    <TransactionSearch
        :transactions="transactions"
        :server-search-options="serverSearchOptions"
        title="Financial Status"
    />
</AppLayout>
</template>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue'
    import TransactionSearch from '../../Components/templates/TransactionSearch.vue';

    defineProps({
        transactions: {
            type: Array,
            default: () => []
        },
        serverSearchOptions: {
            type: Object,
            default: () => ({})
        },
    })
</script>

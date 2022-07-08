<template>
<AppLayout @back="$inertia.visit(route('finance'))" :show-back-button="true">
    <FinanceTemplate title="Transactions" :accounts="accounts">
        <component :is="listComponent"
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
    import TransactionTemplate from '@/Components/templates/TransactionTemplate.vue';
    import { computed } from 'vue';

    const props = defineProps({
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
        accountId: {
            type: [Number, null],
        }
    })

    const listComponent = computed(() => {
        return props.accountId ? TransactionTemplate : TransactionSearch
    })
</script>

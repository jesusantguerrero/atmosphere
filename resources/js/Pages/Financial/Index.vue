<template>
<AppLayout>
    <div class="px-6 pb-20 mx-auto mt-5 max-w-screen-2xl lg:px-8">
        <section-title type="secondary"> Report</section-title>
        <TransactionsTable
            table-label="Transactions"
            class="pt-3 mt-5 "
            table-class="overflow-auto bg-white border rounded-lg shadow-md "
            allow-select
            :transactions="transactions"
            :parser="transactionDBToTransaction"
        />
    </div>
</AppLayout>
</template>

<script setup>
    import AppLayout from '@/Layouts/AppLayout'
    import TransactionsTable from "@/Components/organisms/TransactionsTable";
    import { transactionDBToTransaction } from '@/utils/transactions';

    defineProps({
        transactions: {
            type: Array,
            default: () => []
        }
    })
</script>

<script setup lang="ts">
import { onMounted, ref } from 'vue';

import TransactionsTable from '@/domains/transactions/components/TransactionsList.vue';
import LogerButton from './atoms/LogerButton.vue';

import { transactionDBToTransaction } from '@/domains/transactions';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    occurrenceId: {
        type: Number
    },
    conditions: {
        type: Array
    }
})

const transactions = ref([]);
const isCalled = ref(false);

onMounted(async () => {
    if (props.occurrenceId && !isCalled.value) {
        const data = await fetch(`/housing/occurrence/${props.occurrenceId}/preview`)
        transactions.value = await data.json()
        isCalled.value = true
    }
})

const load = () => {
    axios.post(`/housing/occurrence/${props.occurrenceId}/load`).then(() => {
        router.reload();
    })
}
</script>


<template>
<article>
    <Header class="flex justify-between py-1">
        <span>
            Transactions ({{ transactions.length }})
        </span>
        <LogerButton variant="inverse" @click="load" v-if="transactions.length">
            Load
        </LogerButton>
    </Header>
    <section class="overflow-auto h-96 ic-scroller">
        <TransactionsTable
            class="w-full"
            table-class="overflow-auto text-sm"
            :transactions="transactions"
            :parser="transactionDBToTransaction"
        />
    </section>
</article>
</template>

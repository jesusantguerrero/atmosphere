<script setup lang="ts">
import { onMounted, ref } from 'vue';

import TransactionsTable from '@/domains/transactions/components/TransactionsList.vue';
import LogerButton from './atoms/LogerButton.vue';

import { transactionDBToTransaction } from '@/domains/transactions';
import { router } from '@inertiajs/vue3';
import { IOccurrenceCheck } from '@/domains/housing/models';

const props = defineProps<{
    occurrence: IOccurrenceCheck;
    conditions: any[]
}>();

const transactions = ref([]);
const isCalled = ref(false);

onMounted(async () => {
    if (props.occurrence.id && !isCalled.value) {
        const data = await fetch(`/housing/occurrences/${props.occurrence.id}/preview`)
        transactions.value = await data.json()
        isCalled.value = true
    }
})

const load = () => {
    axios.post(`/housing/occurrences/${props.occurrenceId}/load`).then(() => {
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
        {{ occurrence }}
    </section>
</article>
</template>

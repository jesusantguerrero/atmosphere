<script setup lang="ts">
import { removeTransaction } from '..';
import { ITransaction } from '../models';
import { useTransactionModal } from '../useTransactionModal';
import NextPaymentItem from './NextPaymentItem.vue';

defineProps<{
    payments: ITransaction
}>()

const { openTransactionModal } = useTransactionModal();
const handleEdit = (transaction: ITransaction) => {
    openTransactionModal({
        transactionData: transaction
    })
}
</script>

<template>
<div
    class="mt-4 mb-4 overflow-hidden bg-white sm:rounded-lg"
    :class="cardShadow"
>
    <h4 class="pb-2 font-bold">Next Payments</h4>
    <section class="space-y-2">
        <NextPaymentItem
            v-for="transaction in payments"
            :key="transaction.id"
            :payment="transaction"
            @edit="handleEdit"
            @deleted="removeTransaction"
        />
    </section>
</div>
</template>

<script setup lang="ts">
import { removeTransaction } from '..';
import { ITransaction } from '../models';
import { useTransactionModal } from '../useTransactionModal';
import NextPaymentItem from './NextPaymentItem.vue';

const props = withDefaults(defineProps<{
    payments: ITransaction[];
    title?: string
    emitActions?: boolean
}>(), {
    title: 'Next Payments'
})

const emit = defineEmits(['action', 'delete'])
const { openTransactionModal } = useTransactionModal();
const handleEdit = (transaction: ITransaction) => {
    if (props.emitActions) {
        emit('action', transaction);
    } else {
        openTransactionModal({
            transactionData: transaction
        })
    }
}

const handleRemove = (transaction: ITransaction) => {
    if (props.emitActions) {
        emit('delete', transaction);
    } else {
        removeTransaction(transaction);
    }
}
</script>

<template>
<div
    class="mt-4 mb-4 overflow-hidden bg-white sm:rounded-lg"
    :class="cardShadow"
>
    <h4 class="pb-2 font-bold"> {{ title }} </h4>
    <section class="space-y-2">
        <NextPaymentItem
            v-for="transaction in payments"
            :key="transaction.id"
            :payment="transaction"
            @edit="handleEdit"
            @deleted="handleRemove"
        >
            <template #left-action-button>
                <slot name="left-action-button" :resource="transaction" />
            </template>
            <template #date>
                <slot name="date" :resource="transaction" />
            </template>
        </NextPaymentItem>
    </section>
</div>
</template>

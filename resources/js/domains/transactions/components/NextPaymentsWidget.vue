<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { removeTransaction } from '..';
import { ITransaction } from '../models';
import { useTransactionModal } from '../useTransactionModal';
import NextPaymentItem from './NextPaymentItem.vue';

const { t } = useI18n();

const props = withDefaults(defineProps<{
    payments: ITransaction[];
    title?: string
    emitActions?: boolean
    hideTitle?: boolean
}>(), {
    hideTitle: false,
});

// Default the title to the translated "Next Payments" so consumers that don't
// pass an explicit title still get the locale-aware label.
const resolvedTitle = computed(() => props.title ?? t('Next Payments'));

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
    <h4 v-if="!hideTitle" class="pb-2 font-bold">{{ resolvedTitle }}</h4>
    <section class="space-y-2" v-if="payments?.length">
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
    <div v-else class="flex flex-col items-center justify-center py-8 px-4 text-center">
        <div class="text-3xl mb-2">✅</div>
        <p class="text-sm font-medium text-body">{{ $t('Nothing due right now') }}</p>
        <p class="mt-1 text-xs text-body-1/60 max-w-xs">
            {{ $t('Credit card cuts, planned payments, and budget reminders will appear here as they come up.') }}
        </p>
    </div>
</div>
</template>

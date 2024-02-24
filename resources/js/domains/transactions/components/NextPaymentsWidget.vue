<script setup lang="ts">
import { ITransaction } from '../models';
import NextPaymentItem from './NextPaymentItem.vue';

defineProps<{
    payments: ITransaction
}>()
</script>

<template>
<div
    class="mt-4 mb-10 overflow-hidden bg-white sm:rounded-lg"
    :class="cardShadow"
>
    <h4 class="p-4 pb-2 font-bold">Next Payments</h4>
    <section class="space-y-2 mb-4">
        <NextPaymentItem
            v-for="payment in payments"
            :key="payment.id"
            :payment="payment"
        />
    </section>
</div>
</template>

<template>
    <section class="payment px-4 flex justify-between">
        <div>
            <SectionTitle>
                {{ formatMoney(payment.amount) }}
            </SectionTitle>
            <span class="text-sm text-body-1/80">
                {{ payment.name }}
            </span>
        </div>
        <div>
            <span class="text-secondary bg-secondary/10 px-4 rounded-3xl py-1.5 text-xs">
                Due in 2 days
            </span>
        </div>
</section>
</template>

<script setup>
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import { formatMoney } from '@/utils';
import { AtBadge } from "atmosphere-ui"


defineProps({
    payment: {
        type: Object
    }
})
</script>

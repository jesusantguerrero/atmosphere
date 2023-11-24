<script setup lang="ts">
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import { formatDate, formatMoney } from '@/utils';
import { AtBadge } from "atmosphere-ui"


defineProps<{
    payment: {
        amount: number;
        name: string;
        due_date: string;
    }
}>();
</script>

<template>
    <section class="flex justify-between px-4 payment">
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
                {{ formatDate(payment.due_date) }}
            </span>
        </div>
</section>
</template>


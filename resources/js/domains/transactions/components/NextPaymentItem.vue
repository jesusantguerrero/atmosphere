<script setup lang="ts">
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import { formatDate, formatMoney } from '@/utils';
import { ITransaction } from '../models';

defineProps<{
    payment: ITransaction
}>();
</script>

<template>
    <section class="flex justify-between px-4 payment">
        <div>
            <SectionTitle>
                {{ formatMoney(payment.total) }}
            </SectionTitle>
            <span class="text-sm text-body-1/80">
                {{ payment.description }}
            </span>
        </div>
        <div>
            <span class="text-secondary bg-secondary/10 px-4 rounded-3xl py-1.5 text-xs">
                {{ formatDate(payment.date) }}
            </span>
        </div>
    </section>
</template>


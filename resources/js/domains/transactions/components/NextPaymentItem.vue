<script setup lang="ts">
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import { formatDate } from '@/utils';
import { ITransaction } from '../models';
import MoneyPresenter from '@/Components/molecules/MoneyPresenter.vue';

defineProps<{
    payment: ITransaction
}>();

defineEmits(['edit', 'deleted']);
</script>

<template>
    <article class="flex justify-between px-4 payment group">
        <section class="flex">
            <slot name="left-action-button">
                <button class="text-gray-400 hidden group-hover:inline-block transition cursor-pointer hover:text-red-400 focus:outline-none" @click="$emit('deleted', payment)">
                    <IMdiTrash />
                 </button>
            </slot>
             <section>
                 <SectionTitle>
                     <MoneyPresenter :value="payment.total" />
                 </SectionTitle>
                 <span class="text-sm text-body-1/80">
                     {{ payment.description }}
                 </span>
             </section>
        </section>
        <section class="items-center">
            <slot name="date">
                <span title="Approve transaction" class="text-secondary bg-secondary/10 px-4 rounded-3xl py-1.5 text-xs cursor-pointer" @click="$emit('edit', payment)">
                    {{ formatDate(payment.date) }}
                </span>
            </slot>
        </section>
    </article>
</template>


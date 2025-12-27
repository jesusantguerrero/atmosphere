<script setup lang="ts">
import { computed } from 'vue';
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import { formatDate } from '@/utils';
import { ITransaction } from '../models';
import MoneyPresenter from '@/Components/molecules/MoneyPresenter.vue';

const props = defineProps<{
    payment: ITransaction
}>();

defineEmits(['edit', 'deleted']);

// Calculate days overdue for credit card payments
const daysOverdue = computed(() => {
    if (props.payment.type !== 'credit_card_payment') return 0;

    const today = new Date();
    const dueDate = new Date(props.payment.date);
    const diffTime = today.getTime() - dueDate.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    return Math.max(0, diffDays);
});

// Determine urgency level
const urgencyLevel = computed(() => {
    if (props.payment.type !== 'credit_card_payment') return 'normal';
    if (daysOverdue.value > 10) return 'critical';
    if (daysOverdue.value >= 5) return 'notice';
    return 'warning';
});

// Badge text and styling
const badgeConfig = computed(() => {
    const level = urgencyLevel.value;
    if (level === 'critical') {
        return {
            text: 'Critical',
            bgColor: 'bg-red-600',
            icon: 'IMdiAlertCircle'
        };
    }
    if (level === 'notice') {
        return {
            text: 'Notice',
            bgColor: 'bg-amber-500',
            icon: 'IMdiAlert'
        };
    }
    return {
        text: 'Credit Card',
        bgColor: 'bg-blue-500',
        icon: 'IMdiCreditCard'
    };
});

// Border and background styling
const borderBgConfig = computed(() => {
    const level = urgencyLevel.value;
    if (level === 'critical') {
        return {
            border: 'border-l-red-600',
            bg: 'bg-red-50/40 hover:bg-red-50/60'
        };
    }
    if (level === 'notice') {
        return {
            border: 'border-l-amber-500',
            bg: 'bg-amber-50/30 hover:bg-amber-50/50'
        };
    }
    return {
        border: 'border-l-blue-400',
        bg: 'bg-blue-50/20 hover:bg-blue-50/30'
    };
});

// Date badge styling
const dateBadgeConfig = computed(() => {
    const level = urgencyLevel.value;
    if (level === 'critical') {
        return 'text-red-700 bg-red-100 hover:bg-red-200';
    }
    if (level === 'notice') {
        return 'text-amber-700 bg-amber-100 hover:bg-amber-200';
    }
    return 'text-blue-700 bg-blue-100 hover:bg-blue-200';
});
</script>

<template>
    <article
        class="flex justify-between px-4 payment group border-l-4 transition"
        :class="[
            payment.type === 'credit_card_payment'
                ? borderBgConfig.border + ' ' + borderBgConfig.bg
                : 'border-l-transparent hover:bg-gray-50'
        ]"
    >
        <section class="flex gap-3 flex-1">
            <slot name="left-action-button">
                <button class="text-gray-400 hidden group-hover:inline-block transition cursor-pointer hover:text-red-400 focus:outline-none" @click="$emit('deleted', payment)">
                    <IMdiTrash />
                 </button>
            </slot>
            <section class="flex-1">
                <div class="flex items-center gap-2">
                    <SectionTitle>
                        <MoneyPresenter :value="payment.total" />
                    </SectionTitle>
                    <span
                        v-if="payment.type === 'credit_card_payment'"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold text-white"
                        :class="badgeConfig.bgColor"
                        :title="`${badgeConfig.text}${daysOverdue > 0 ? ` - ${daysOverdue} days overdue` : ''}`"
                    >
                        <component :is="badgeConfig.icon" class="w-3 h-3" />
                        {{ badgeConfig.text }}
                    </span>
                </div>
                <span class="text-sm text-body-1/80">
                    {{ payment.description }}
                </span>
            </section>
        </section>
        <section class="flex items-center">
            <slot name="date">
                <span
                    title="Approve transaction"
                    class="px-4 rounded-3xl py-1.5 text-xs cursor-pointer transition"
                    :class="payment.type === 'credit_card_payment'
                        ? dateBadgeConfig
                        : 'text-secondary bg-secondary/10 hover:bg-secondary/20'"
                    @click="$emit('edit', payment)"
                >
                    {{ formatDate(payment.date) }}
                </span>
            </slot>
        </section>
    </article>
</template>


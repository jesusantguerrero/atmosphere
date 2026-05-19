<script setup lang="ts">
import { computed } from 'vue';
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import { formatDate } from '@/utils';
import { ITransaction } from '../models';
import MoneyPresenter from '@/Components/molecules/MoneyPresenter.vue';

const props = defineProps<{
    payment: ITransaction
}>();

defineEmits(['edit', 'deleted', 'pay']);

// Billing-cycle status badge (only renders when this row is a credit-card
// cycle, identified by `payment.status` being one of the BillingCycle
// constants). Separate from urgencyLevel below — urgency is "how late is
// this", status is "is it paid yet". Both can co-exist on the same row.
const STATUS_META: Record<string, { label: string; classes: string }> = {
    PENDING:         { label: 'Pending',   classes: 'bg-gray-100 text-gray-700 ring-gray-200' },
    PARTIALLY_PAID:  { label: 'Partial',   classes: 'bg-amber-50 text-amber-700 ring-amber-200' },
    LATE:            { label: 'Late',      classes: 'bg-red-50 text-red-700 ring-red-200' },
    PAID:            { label: 'Paid',      classes: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
    CANCELLED:       { label: 'Cancelled', classes: 'bg-gray-50 text-gray-500 ring-gray-200' },
};

const cycleStatus = computed(() => {
    const raw = (props.payment as any).status;
    if (!raw || typeof raw !== 'string') return null;
    return STATUS_META[raw.toUpperCase()] ?? null;
});

// Remaining balance for partially-paid cycles. Used for the Pay button so
// the user pays only the outstanding amount, not the original total.
const remainingBalance = computed(() => {
    const p: any = props.payment;
    const total = Number(p.total ?? 0);
    const paid = Number(p.paid ?? 0);
    const remaining = total - paid;
    return remaining > 0 ? remaining : 0;
});

const isCycle = computed(() => cycleStatus.value !== null);
const isSettled = computed(() => {
    const raw = (props.payment as any).status;
    return raw === 'PAID' || raw === 'CANCELLED';
});

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
            classes: 'bg-red-50 text-red-700 ring-red-100',
            dotColor: 'bg-red-500',
            icon: 'IMdiAlertCircle'
        };
    }
    if (level === 'notice') {
        return {
            text: 'Notice',
            classes: 'bg-amber-50 text-amber-700 ring-amber-100',
            dotColor: 'bg-amber-500',
            icon: 'IMdiAlert'
        };
    }
    return {
        text: 'Credit Card',
        classes: 'bg-blue-50 text-blue-700 ring-blue-100',
        dotColor: 'bg-blue-500',
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
        return 'text-red-700 bg-red-50 ring-red-100 hover:bg-red-100/70';
    }
    if (level === 'notice') {
        return 'text-amber-700 bg-amber-50 ring-amber-100 hover:bg-amber-100/70';
    }
    return 'text-blue-700 bg-blue-50 ring-blue-100 hover:bg-blue-100/70';
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
                <button class="text-gray-400 inline-block md:opacity-0 md:group-hover:opacity-100 transition cursor-pointer hover:text-red-400 focus:outline-none" @click="$emit('deleted', payment)">
                    <IMdiTrash />
                 </button>
            </slot>
            <section class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <SectionTitle class="tabular-nums">
                        <MoneyPresenter :value="payment.total" />
                    </SectionTitle>
                    <span
                        v-if="payment.type === 'credit_card_payment'"
                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-semibold uppercase tracking-wide ring-1 ring-inset"
                        :class="badgeConfig.classes"
                        :title="`${badgeConfig.text}${daysOverdue > 0 ? ` - ${daysOverdue} days overdue` : ''}`"
                    >
                        <component :is="badgeConfig.icon" class="w-3 h-3" />
                        {{ badgeConfig.text }}
                    </span>
                    <!-- Cycle status badge — orthogonal to urgency. Shows actual
                         settlement state (PENDING / PARTIALLY_PAID / LATE / PAID /
                         CANCELLED) so a glance answers "is this bill done?". -->
                    <span
                        v-if="cycleStatus"
                        class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold uppercase tracking-wide ring-1 ring-inset"
                        :class="cycleStatus.classes"
                    >
                        {{ cycleStatus.label }}
                    </span>
                </div>
                <span class="text-sm text-body-1/70 truncate block">
                    {{ payment.description }}
                </span>
                <!-- Show remaining balance for partial-pay cycles so the user
                     knows what's left without doing the math themselves. -->
                <span
                    v-if="isCycle && (payment as any).paid > 0 && !isSettled"
                    class="text-[11px] text-body-1/60 tabular-nums"
                >
                    {{ $t('Paid') }} <MoneyPresenter :value="(payment as any).paid" /> ·
                    <MoneyPresenter :value="remainingBalance" /> {{ $t('remaining') }}
                </span>
            </section>
        </section>
        <section class="flex items-center pl-2 gap-2">
            <!-- Pay this cycle CTA. Always visible on cycle rows that aren't
                 already settled. Emits 'pay' so the parent can open a transfer
                 modal pre-filled with the remaining balance; the auto-link
                 listener attaches the resulting Payment to this cycle. -->
            <button
                v-if="isCycle && !isSettled"
                type="button"
                class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-primary text-white shadow-sm hover:bg-primary-dark transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                :title="$t('Pay this cycle')"
                @click.stop="$emit('pay', payment)"
            >
                <IMdiCash class="w-3 h-3" />
                {{ $t('Pay') }}
            </button>
            <slot name="date">
                <button
                    type="button"
                    title="Approve transaction"
                    class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium tabular-nums whitespace-nowrap ring-1 ring-inset transition cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                    :class="payment.type === 'credit_card_payment'
                        ? dateBadgeConfig
                        : 'text-body-1/70 bg-base-lvl-2 ring-base hover:bg-base-lvl-1 hover:text-body'"
                    @click="$emit('edit', payment)"
                >
                    {{ formatDate(payment.date) }}
                </button>
            </slot>
        </section>
    </article>
</template>


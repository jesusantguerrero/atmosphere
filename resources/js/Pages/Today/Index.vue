<script setup lang="ts">
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { format, parseISO } from 'date-fns';

import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import { formatMoney } from '@/utils';
import { useTransactionModal } from '@/domains/transactions/useTransactionModal';

interface MoneyPayload {
    today_spent: number;
    month_assigned: number;
    month_spent: number;
    month_remaining: number;
}

interface AttentionItem {
    id: string;
    message: string;
    cta: string | null;
    link: string | null;
}

interface TodayItem {
    id: number;
    dateable_type: string | null;
    date: string;
    status: string;
}

interface UpcomingItem {
    kind: 'billing_cycle' | 'utility';
    id: string;
    name: string | null;
    account_id: number | null;
    total: number | null;
    due_at: string;
    days_until: number;
}

const props = defineProps<{
    today: {
        money: MoneyPayload;
        attention: AttentionItem[];
        today: TodayItem[];
        upcoming: UpcomingItem[];
    };
}>();

const { openTransactionModal } = useTransactionModal();

const monthProgressPercent = computed(() => {
    const assigned = props.today.money.month_assigned;
    if (assigned <= 0) return 0;
    const ratio = props.today.money.month_spent / assigned;
    return Math.min(100, Math.max(0, Math.round(ratio * 100)));
});

const monthProgressClass = computed(() => {
    const pct = monthProgressPercent.value;
    if (pct > 100 || props.today.money.month_remaining < 0) return 'bg-error';
    if (pct > 70) return 'bg-warning';
    return 'bg-success';
});

const formatDueDate = (iso: string) => {
    try {
        return format(parseISO(iso), 'MMM d');
    } catch (e) {
        return iso;
    }
};

// "in 3 days" / "tomorrow" / "today" / "2 days overdue" — without depending on the
// raw date string (server already computed days_until against its own `now`).
const relativeDueLabel = (daysUntil: number) => {
    if (daysUntil < 0) return Math.abs(daysUntil) === 1 ? '1 day overdue' : `${Math.abs(daysUntil)} days overdue`;
    if (daysUntil === 0) return 'today';
    if (daysUntil === 1) return 'tomorrow';
    return `in ${daysUntil} days`;
};

const todayLabel = computed(() => format(new Date(), 'EEEE, MMM d'));

const openQuickAdd = () => {
    openTransactionModal({ mode: 'WITHDRAW' });
};
</script>

<template>
    <AppLayout :title="$t('Today')">
        <main class="px-4 mx-auto mt-5 mb-10 max-w-screen-2xl sm:px-6 lg:px-8 space-y-4">
            <header class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-body">{{ $t('Today') }}</h1>
                    <p class="text-sm text-body-1/70 capitalize">{{ todayLabel }}</p>
                </div>
                <LogerButton variant="primary" rounded @click="openQuickAdd">
                    <i class="fa fa-plus mr-2" />
                    {{ $t('Add expense') }}
                </LogerButton>
            </header>

            <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- MONEY widget — today's spend + month progress -->
                <article class="bg-base-lvl-3 rounded-lg border border-base p-5">
                    <header class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                            {{ $t('Money') }}
                        </h2>
                        <Link href="/budgets" class="text-xs text-primary hover:underline">
                            {{ $t('Open budget') }}
                        </Link>
                    </header>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-body-1/60 uppercase tracking-wide">{{ $t('Spent today') }}</p>
                            <p class="text-2xl font-bold text-body mt-1">{{ formatMoney(today.money.today_spent) }}</p>
                        </div>
                        <div>
                            <div class="flex items-baseline justify-between mb-1.5">
                                <span class="text-xs text-body-1/60 uppercase tracking-wide">
                                    {{ $t('This month') }}
                                </span>
                                <span class="text-sm font-medium" :class="today.money.month_remaining < 0 ? 'text-error' : 'text-body-1'">
                                    {{ formatMoney(today.money.month_spent) }} / {{ formatMoney(today.money.month_assigned) }}
                                </span>
                            </div>
                            <div class="h-2 rounded-full bg-base-lvl-1 overflow-hidden">
                                <div
                                    class="h-full transition-all"
                                    :class="monthProgressClass"
                                    :style="{ width: `${monthProgressPercent}%` }"
                                />
                            </div>
                            <p class="text-xs text-body-1/60 mt-1.5">
                                {{ today.money.month_remaining >= 0
                                    ? $t('{amount} left', { amount: formatMoney(today.money.month_remaining) })
                                    : $t('Over by {amount}', { amount: formatMoney(Math.abs(today.money.month_remaining)) })
                                }}
                            </p>
                        </div>
                    </div>
                </article>

                <!-- ATTENTION widget — unread watchlist threshold alerts this month -->
                <article class="bg-base-lvl-3 rounded-lg border border-base p-5">
                    <header class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                            {{ $t('Attention') }}
                            <span v-if="today.attention.length" class="ml-1 text-error">({{ today.attention.length }})</span>
                        </h2>
                        <Link href="/notifications" class="text-xs text-primary hover:underline">
                            {{ $t('View all') }}
                        </Link>
                    </header>
                    <div v-if="today.attention.length" class="space-y-2">
                        <Link
                            v-for="item in today.attention"
                            :key="item.id"
                            :href="item.link || '/notifications'"
                            class="block p-2.5 rounded-md bg-error/5 border border-error/20 hover:border-error/40 transition"
                        >
                            <p class="text-sm font-medium text-body">{{ item.message }}</p>
                            <p v-if="item.cta" class="text-xs text-error mt-0.5">{{ item.cta }} →</p>
                        </Link>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="text-3xl mb-1.5">✅</div>
                        <p class="text-sm text-body-1">{{ $t('Nothing crossing thresholds') }}</p>
                        <p class="text-xs text-body-1/60 mt-1 max-w-xs">
                            {{ $t('Set a target on a watchlist to start getting alerts here.') }}
                        </p>
                    </div>
                </article>

                <!-- TODAY widget — Planner items due today -->
                <article class="bg-base-lvl-3 rounded-lg border border-base p-5">
                    <header class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                            {{ $t('Due today') }}
                            <span v-if="today.today.length" class="ml-1 text-body-1">({{ today.today.length }})</span>
                        </h2>
                    </header>
                    <div v-if="today.today.length" class="space-y-2">
                        <div
                            v-for="item in today.today"
                            :key="item.id"
                            class="flex items-center gap-3 px-3 py-2 rounded-md bg-base-lvl-2"
                        >
                            <i class="fa fa-circle text-xs text-primary" />
                            <span class="flex-1 text-sm text-body">
                                {{ item.dateable_type?.split('\\').pop() ?? $t('Item') }}
                            </span>
                            <span class="text-xs text-body-1/60 capitalize">{{ item.status }}</span>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="text-3xl mb-1.5">🎯</div>
                        <p class="text-sm text-body-1">{{ $t('No scheduled items today') }}</p>
                        <p class="text-xs text-body-1/60 mt-1 max-w-xs">
                            {{ $t('Reminders, planned transactions, and chores show up here as they come due.') }}
                        </p>
                    </div>
                </article>

                <!-- UPCOMING widget — bills + utilities due in next 7 days. v0.2 (HM-1):
                     also surfaces Occurrence type=utility records (water/electricity/etc.). -->
                <article class="bg-base-lvl-3 rounded-lg border border-base p-5">
                    <header class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                            {{ $t('Upcoming') }}
                            <span v-if="today.upcoming.length" class="ml-1 text-body-1">({{ today.upcoming.length }})</span>
                        </h2>
                    </header>
                    <div v-if="today.upcoming.length" class="space-y-2">
                        <component
                            :is="item.kind === 'billing_cycle' ? 'button' : 'div'"
                            v-for="item in today.upcoming"
                            :key="item.id"
                            type="button"
                            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-md bg-base-lvl-2 transition text-left"
                            :class="item.kind === 'billing_cycle' ? 'hover:bg-base-lvl-1 cursor-pointer' : ''"
                            @click="item.kind === 'billing_cycle' && item.account_id ? router.visit(`/finance/accounts/${item.account_id}`) : null"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i
                                    class="fa text-sm"
                                    :class="item.kind === 'billing_cycle'
                                        ? 'fa-credit-card text-warning'
                                        : 'fa-bolt text-secondary'"
                                />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-body truncate">{{ item.name ?? $t('Item') }}</p>
                                    <p class="text-xs text-body-1/60">
                                        {{ formatDueDate(item.due_at) }}
                                        <span class="mx-1">·</span>
                                        <span :class="item.days_until < 0 ? 'text-error font-medium' : ''">
                                            {{ relativeDueLabel(item.days_until) }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <span
                                v-if="item.total !== null"
                                class="text-sm font-bold text-error flex-shrink-0"
                            >
                                {{ formatMoney(item.total) }}
                            </span>
                        </component>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="text-3xl mb-1.5">📅</div>
                        <p class="text-sm text-body-1">{{ $t('Nothing due this week') }}</p>
                        <p class="text-xs text-body-1/60 mt-1 max-w-xs">
                            {{ $t('Credit card cycles and recurring utilities show up here within 7 days of their due date.') }}
                        </p>
                    </div>
                </article>
            </section>
        </main>
    </AppLayout>
</template>

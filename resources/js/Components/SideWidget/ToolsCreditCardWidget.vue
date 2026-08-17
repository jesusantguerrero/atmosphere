<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';

import { IAccount } from '@/domains/transactions/models';
import { isCreditCard } from '@/domains/transactions';
import { formatMoney, formatMonth } from '@/utils';
import { useTransactionModal } from '@/domains/transactions/useTransactionModal';

interface PayInFullItem {
    account_id: number;
    account_name: string;
    total: number;
    due_at: string;
    days_until: number;
    is_overdue: boolean;
}

interface InactiveItem {
    account_id: number;
    account_name: string;
    last_used_at: string | null;
}

interface SummaryPayload {
    payInFull: PayInFullItem[];
    inactive: InactiveItem[];
}

const props = defineProps<{
    accounts: IAccount[];
}>();

const summary = ref<SummaryPayload | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const { openTransactionModal } = useTransactionModal();

const fetchSummary = async (): Promise<void> => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get('/credit-card-summary');
        summary.value = data;
    } catch {
        error.value = 'Could not load credit card summary.';
    } finally {
        loading.value = false;
    }
};

const creditCards = computed<IAccount[]>(() =>
    props.accounts.filter(isCreditCard)
);

// Total position — the at-a-glance hero. Debt = absolute sum of negative
// balances (the money owed); Available = sum of (limit - debt) per card.
const totalDebt = computed<number>(() =>
    creditCards.value.reduce((sum, card) => {
        const balance = Number(card.balance ?? 0);
        return sum + Math.abs(Math.min(0, balance));
    }, 0)
);

const totalLimit = computed<number>(() =>
    creditCards.value.reduce((sum, card) => sum + Number(card.credit_limit ?? 0), 0)
);

const totalAvailable = computed<number>(() => Math.max(0, totalLimit.value - totalDebt.value));

const utilizationPct = computed<number>(() =>
    totalLimit.value > 0 ? Math.round((totalDebt.value / totalLimit.value) * 100) : 0
);

const utilizationTone = computed<'over' | 'high' | 'ok'>(() => {
    if (utilizationPct.value >= 80) return 'over';
    if (utilizationPct.value >= 50) return 'high';
    return 'ok';
});

// Closing soon — informational. Computes the next closing date for each
// card based on credit_closing_day and today's calendar position. We show
// only cards with debt (no point flagging zero-balance closings).
interface ClosingSoonRow {
    account_id: number;
    name: string;
    debt: number;
    days_until: number;
}

const closingSoon = computed<ClosingSoonRow[]>(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return creditCards.value
        .map((card): ClosingSoonRow | null => {
            const balance = Number(card.balance ?? 0);
            const debt = Math.abs(Math.min(0, balance));
            if (debt < 0.01) return null;

            const closingDay = Number(card.credit_closing_day);
            if (!closingDay) return null;

            const candidate = new Date(today.getFullYear(), today.getMonth(), closingDay);
            if (candidate < today) {
                candidate.setMonth(candidate.getMonth() + 1);
            }
            const daysUntil = Math.round((candidate.getTime() - today.getTime()) / 86_400_000);

            return {
                account_id: card.id,
                name: card.name,
                debt,
                days_until: daysUntil,
            };
        })
        .filter((row): row is ClosingSoonRow => row !== null)
        .sort((a, b) => a.days_until - b.days_until)
        .slice(0, 4);
});

// Cards that need money RIGHT NOW. The A1 section. Cards already in the
// payInFull list are excluded from "Closing soon" so we don't double-show.
const payInFullIds = computed<Set<number>>(() => new Set((summary.value?.payInFull ?? []).map((p) => p.account_id)));
const closingSoonFiltered = computed<ClosingSoonRow[]>(() =>
    closingSoon.value.filter((row) => !payInFullIds.value.has(row.account_id))
);

const dayLabel = (daysUntil: number, isOverdue: boolean): string => {
    if (isOverdue) {
        const past = Math.abs(daysUntil);
        if (past === 0) return 'today';
        if (past === 1) return '1 day overdue';
        return `${past} days overdue`;
    }
    if (daysUntil === 0) return 'today';
    if (daysUntil === 1) return 'in 1 day';
    return `in ${daysUntil}d`;
};

const formatLastUsed = (iso: string | null): string => {
    if (!iso) return 'never used';
    const date = new Date(iso + 'T12:00:00');
    return formatMonth(date, 'MMM yyyy');
};

const handlePayInFull = (item: PayInFullItem): void => {
    // Open transfer modal preset toward the card account so the user lands on
    // a half-filled "transfer to {Card}" form. Amount stays open — they may
    // want to enter a different number than the surfaced statement balance.
    openTransactionModal({
        mode: 'TRANSFER',
        transactionData: {
            counter_account_id: item.account_id,
            total: item.total,
            description: `Pay ${item.account_name} in full`,
        },
    });
};

onMounted(fetchSummary);
</script>

<template>
    <main class="flex flex-col h-full min-h-0">
        <section class="flex-1 min-h-0 overflow-y-auto pr-1">
            <div v-if="loading" class="text-xs text-body-1/50 py-6 text-center">
                {{ $t('Loading…') }}
            </div>

            <div v-else-if="error" class="text-xs text-error py-6 text-center">{{ error }}</div>

            <template v-else-if="summary">
                <!-- Section 1: PAY IN FULL — A1 priority. Cards with unpaid
                     statement; pre-due (PRE_DUE window) or overdue. The
                     reason this widget exists. -->
                <article v-if="summary.payInFull.length" class="mb-4">
                    <h3 class="text-[11px] font-semibold uppercase tracking-wide text-error mb-2 flex items-center gap-1.5">
                        <i class="fa fa-triangle-exclamation" />
                        {{ $t('Pay in full') }}
                    </h3>
                    <div class="space-y-2">
                        <div
                            v-for="item in summary.payInFull"
                            :key="item.account_id"
                            class="rounded-xl border p-3"
                            :class="item.is_overdue
                                ? 'bg-error/5 border-error/40'
                                : 'bg-amber-500/5 border-amber-500/30'"
                        >
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-body truncate">{{ item.account_name }}</p>
                                    <p
                                        class="text-[11px] mt-0.5"
                                        :class="item.is_overdue ? 'text-error font-semibold' : 'text-body-1/70'"
                                    >
                                        {{ formatMoney(item.total) }}
                                        <span class="mx-1 text-body-1/40">·</span>
                                        {{ dayLabel(item.days_until, item.is_overdue) }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="w-full text-xs px-3 py-1.5 rounded-lg bg-primary text-white font-semibold shadow-sm hover:bg-primary/90 transition"
                                @click="handlePayInFull(item)"
                            >
                                {{ $t('Pay in full') }} →
                            </button>
                        </div>
                    </div>
                </article>

                <!-- Section 2: TOTAL POSITION — at-a-glance summary across all
                     cards. The "what do I owe" answer. -->
                <article v-if="creditCards.length" class="mb-4">
                    <h3 class="text-[11px] font-semibold uppercase tracking-wide text-body-1/50 mb-2">
                        {{ $t('Total position') }}
                    </h3>
                    <div class="bg-base-lvl-2 rounded-xl border border-base p-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-body-1/50">{{ $t('Debt') }}</p>
                                <p class="text-base font-bold tabular-nums" :class="totalDebt > 0 ? 'text-error' : 'text-body'">
                                    {{ formatMoney(totalDebt) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-body-1/50">{{ $t('Available') }}</p>
                                <p class="text-base font-bold tabular-nums text-body">{{ formatMoney(totalAvailable) }}</p>
                            </div>
                        </div>
                        <p class="text-[11px] text-body-1/60 pt-2 mt-2 border-t border-base">
                            {{ creditCards.length }} {{ $t(creditCards.length === 1 ? 'card' : 'cards') }}
                            <span class="mx-1 text-body-1/30">·</span>
                            <span
                                class="font-semibold tabular-nums"
                                :class="{
                                    'text-error': utilizationTone === 'over',
                                    'text-amber-500': utilizationTone === 'high',
                                    'text-body': utilizationTone === 'ok',
                                }"
                            >
                                {{ utilizationPct }}%
                            </span>
                            {{ $t('utilization') }}
                        </p>
                    </div>
                </article>

                <!-- Section 3: CLOSING SOON — informational. Shows cards with
                     debt approaching their next closing date so you can plan
                     cash. Cards already in payInFull are filtered out. -->
                <article v-if="closingSoonFiltered.length" class="mb-4">
                    <h3 class="text-[11px] font-semibold uppercase tracking-wide text-body-1/50 mb-2">
                        {{ $t('Closing soon') }}
                    </h3>
                    <div class="bg-base-lvl-2 rounded-xl border border-base divide-y divide-base">
                        <div
                            v-for="row in closingSoonFiltered"
                            :key="row.account_id"
                            class="flex items-center justify-between px-3 py-2 text-xs"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-body truncate">{{ row.name }}</p>
                                <p class="text-[11px] text-body-1/60">
                                    {{ $t('closes') }} {{ dayLabel(row.days_until, false) }}
                                </p>
                            </div>
                            <p class="font-bold tabular-nums text-body shrink-0 ml-2">
                                {{ formatMoney(row.debt) }}
                            </p>
                        </div>
                    </div>
                </article>

                <!-- Section 4: CONSIDER CLOSING — cleanup task. Cards with no
                     activity in 6+ months and zero balance. Cancellation
                     candidates. Hidden when there are none. -->
                <article v-if="summary.inactive.length" class="mb-3">
                    <h3 class="text-[11px] font-semibold uppercase tracking-wide text-body-1/50 mb-2">
                        {{ $t('Consider closing') }}
                    </h3>
                    <div class="bg-base-lvl-2 rounded-xl border border-base p-3">
                        <p class="text-[11px] text-body-1/60 mb-2">
                            {{ $t('No activity in 6+ months · zero balance') }}
                        </p>
                        <ul class="space-y-1.5 text-xs">
                            <li
                                v-for="card in summary.inactive"
                                :key="card.account_id"
                                class="flex items-center justify-between gap-2"
                            >
                                <span class="truncate text-body">{{ card.account_name }}</span>
                                <span class="text-[11px] text-body-1/50 shrink-0">
                                    {{ $t('last used') }} {{ formatLastUsed(card.last_used_at) }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </article>
            </template>
        </section>

        <footer class="pt-3 mt-2 border-t border-base shrink-0">
            <Link
                href="/finance"
                class="block text-center text-xs text-primary hover:underline"
            >
                {{ $t('Open accounts') }} →
            </Link>
        </footer>
    </main>
</template>

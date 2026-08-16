<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import { formatDate, formatMoney } from '@/utils';
import { useTransactionModal } from '@/domains/transactions/useTransactionModal';
import { useTransactionStore } from '@/store/transactions';

interface MoneyPayload {
    today_spent: number;
    daily_remaining: number;
    month_remaining: number;
    days_in_month_left: number;
    currency_code: string | null;
}

interface TopCategory {
    id: number;
    name: string;
    group: string | null;
    available: number;
    monthly_avg: number;
}

interface UpcomingItem {
    name: string;
    total: number;
    due_at: string;
    days_until: number;
}

interface UpcomingPayload {
    count: number;
    total: number;
    items: UpcomingItem[];
}

interface SummaryPayload {
    money: MoneyPayload;
    topCategories: TopCategory[];
    upcoming: UpcomingPayload;
    ready_to_assign: number;
    overspending: Record<string, number>;
}

const summary = ref<SummaryPayload | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const { openTransactionModal } = useTransactionModal();

const fetchSummary = async (): Promise<void> => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get('/budget-summary');
        summary.value = data;
    } catch {
        error.value = 'Could not load your budget summary.';
    } finally {
        loading.value = false;
    }
};

const todaySpent = computed<number>(() => summary.value?.money.today_spent ?? 0);
const dailySafe = computed<number>(() => summary.value?.money.daily_remaining ?? 0);
const readyToAssign = computed<number>(() => summary.value?.ready_to_assign ?? 0);

// Color signal for a category row:
// - red: overspent (negative available)
// - amber: less than 20% of typical monthly spend left
// - default: room to spare
const categoryTone = (cat: TopCategory): 'over' | 'fund' | 'low' | 'ok' => {
    // A negative available is a true overspend only when Ready-to-Assign is
    // exhausted. While RTA is still positive the category is just unfunded ->
    // amber 'to fund', not red, so the widget agrees with the Budget hero (Fix-2).
    if (cat.available < 0) return readyToAssign.value > 0 ? 'fund' : 'over';
    if (cat.monthly_avg > 0 && cat.available < cat.monthly_avg * 0.2) return 'low';
    return 'ok';
};

const dayLabel = (item: UpcomingItem): string => {
    if (item.days_until === 0) return 'today';
    if (item.days_until === 1) return 'tomorrow';
    // Format as short weekday for the next-7-day window.
    const date = new Date(item.due_at + 'T12:00:00');
    return formatDate(date, undefined, 'EEE');
};

const handleLogExpense = (): void => {
    openTransactionModal({ mode: 'WITHDRAW' });
};

onMounted(fetchSummary);

// Re-pull the summary whenever a transaction is logged/edited/deleted anywhere
// (the store bumps `revision`). Inertia's partial reload doesn't re-run this
// widget's onMounted, so without this 'No spending history yet' and the daily
// numbers stay stale after the first expense.
const transactionStore = useTransactionStore();
watch(() => transactionStore.revision, () => { fetchSummary(); });
</script>

<template>
    <main class="flex flex-col h-full min-h-0">
        <section class="flex-1 min-h-0 overflow-y-auto pr-1">
            <div v-if="loading" class="text-xs text-body-1/50 py-6 text-center">
                {{ $t('Loading…') }}
            </div>

            <div v-else-if="error" class="text-xs text-error py-6 text-center">
                {{ error }}
            </div>

            <template v-else-if="summary">
                <!-- Section 1: Can I spend on…? Per-category remaining for the
                     5 categories the user touches most. The decision-shaped
                     question this section answers. -->
                <article class="mb-4">
                    <h3 class="text-[10px] font-semibold uppercase tracking-wide text-body-1/50 mb-2">
                        {{ $t('Can I spend on…?') }}
                    </h3>
                    <div v-if="summary.topCategories.length" class="bg-base-lvl-2 rounded-xl border border-base divide-y divide-base">
                        <div
                            v-for="cat in summary.topCategories"
                            :key="cat.id"
                            class="flex items-center justify-between px-3 py-2.5 text-xs"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-body truncate">{{ cat.name }}</p>
                                <p v-if="cat.group" class="text-[10px] text-body-1/50 truncate">{{ cat.group }}</p>
                            </div>
                            <div class="text-right shrink-0 ml-2">
                                <p
                                    class="font-bold tabular-nums"
                                    :class="{
                                        'text-error': categoryTone(cat) === 'over',
                                        'text-amber-500': categoryTone(cat) === 'low' || categoryTone(cat) === 'fund',
                                        'text-body': categoryTone(cat) === 'ok',
                                    }"
                                >
                                    {{ formatMoney(cat.available) }}
                                </p>
                                <p
                                    v-if="categoryTone(cat) === 'low'"
                                    class="text-[9px] text-amber-500 font-semibold uppercase tracking-wide"
                                >
                                    {{ $t('low') }}
                                </p>
                                <p
                                    v-else-if="categoryTone(cat) === 'fund'"
                                    class="text-[9px] text-amber-500 font-semibold uppercase tracking-wide"
                                >
                                    {{ $t('to fund') }}
                                </p>
                                <p
                                    v-else-if="categoryTone(cat) === 'over'"
                                    class="text-[9px] text-error font-semibold uppercase tracking-wide"
                                >
                                    {{ $t('over') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="bg-base-lvl-2 rounded-xl border border-base px-3 py-4 text-center">
                        <p class="text-xs text-body-1/60">{{ $t('No spending history yet.') }}</p>
                        <p class="text-[11px] text-body-1/40 mt-0.5">{{ $t('Categories show up here once you log a few expenses.') }}</p>
                    </div>
                </article>

                <!-- Section 2: Today. Action surface — log expense lives here so
                     the widget is part of the logging flow, not just a readout. -->
                <article class="mb-4">
                    <h3 class="text-[10px] font-semibold uppercase tracking-wide text-body-1/50 mb-2">
                        {{ $t('Today') }}
                    </h3>
                    <div class="bg-base-lvl-2 rounded-xl border border-base p-3">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-[10px] text-body-1/50 uppercase tracking-wide">{{ $t('Logged') }}</p>
                                <p class="text-base font-bold text-body tabular-nums">{{ formatMoney(todaySpent) }}</p>
                            </div>
                            <button
                                type="button"
                                class="text-xs px-3 py-1.5 rounded-lg bg-primary text-white font-semibold shadow-sm hover:bg-primary/90 transition"
                                @click="handleLogExpense"
                            >
                                <i class="fa fa-plus text-[10px] mr-1" />
                                {{ $t('Log expense') }}
                            </button>
                        </div>
                        <p class="text-[11px] text-body-1/60 pt-2 border-t border-base">
                            {{ $t('Daily safe') }}:
                            <span class="font-semibold text-body">{{ formatMoney(dailySafe) }}</span>
                        </p>
                    </div>
                </article>

                <!-- Section 3: Next 7 days. Cash-flow context — bills coming
                     mean the available numbers above don't tell the full story. -->
                <article v-if="summary.upcoming.count" class="mb-3">
                    <h3 class="text-[10px] font-semibold uppercase tracking-wide text-body-1/50 mb-2">
                        {{ $t('Next 7 days') }}
                    </h3>
                    <div class="bg-amber-500/5 rounded-xl border border-amber-500/30 p-3">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fa fa-clock text-amber-500 text-xs" />
                            <p class="text-xs font-semibold text-body">
                                {{ summary.upcoming.count }}
                                {{ $t(summary.upcoming.count === 1 ? 'bill due' : 'bills due') }}
                                ·
                                <span class="tabular-nums">{{ formatMoney(summary.upcoming.total) }}</span>
                            </p>
                        </div>
                        <ul class="space-y-1 text-xs">
                            <li
                                v-for="(item, i) in summary.upcoming.items"
                                :key="i"
                                class="flex items-center justify-between gap-2"
                            >
                                <span class="truncate text-body">
                                    {{ item.name }}
                                    <span class="text-body-1/50">({{ dayLabel(item) }})</span>
                                </span>
                                <span class="tabular-nums text-body-1/80 shrink-0">{{ formatMoney(item.total) }}</span>
                            </li>
                        </ul>
                    </div>
                </article>
            </template>
        </section>

        <!-- Footer: full budget escape hatch -->
        <footer class="pt-3 mt-2 border-t border-base shrink-0">
            <Link
                href="/budgets"
                class="block text-center text-xs text-primary hover:underline"
            >
                {{ $t('Open budget') }} →
            </Link>
        </footer>
    </main>
</template>

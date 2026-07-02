<script setup lang="ts">
import { computed, ref, reactive } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { format, parseISO, addMonths, subMonths, startOfMonth } from 'date-fns';
import axios from 'axios';

import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import { formatMoney } from '@/utils';

interface PlanItem {
    category_id: number;
    name: string;
    amount: number;
    notes: string | null;
}

interface PlanGroup {
    group_id: number;
    group_name: string;
    group_total: number;
    items: PlanItem[];
}

const props = defineProps<{
    month: string;          // YYYY-MM-DD (first of month)
    monthLabel: string;     // "July 2026"
    groups: PlanGroup[];
    grandTotal: number;
}>();

const { t } = useI18n();

// Local reactive copy so inputs feel snappy — we upsert per-row on blur.
// Keyed by category_id.
const amounts = reactive<Record<number, string>>({});
const savingIds = ref<Set<number>>(new Set());
const savedIds = ref<Set<number>>(new Set());

for (const group of props.groups) {
    for (const item of group.items) {
        amounts[item.category_id] = item.amount ? String(item.amount) : '';
    }
}

// Recompute totals from the local amounts so the number moves as you type.
const localGroupTotals = computed(() => {
    const out: Record<number, number> = {};
    for (const group of props.groups) {
        let sum = 0;
        for (const item of group.items) {
            const v = Number(amounts[item.category_id] ?? 0) || 0;
            sum += v;
        }
        out[group.group_id] = sum;
    }
    return out;
});

const localGrandTotal = computed(() => {
    return Object.values(localGroupTotals.value).reduce((a, b) => a + b, 0);
});

const saveIntent = async (item: PlanItem) => {
    const raw = amounts[item.category_id];
    const amount = Number(raw ?? 0) || 0;
    // Skip network if unchanged from what the server sent (avoids
    // spamming PUTs when the user just tabs through fields).
    if (amount === item.amount) return;

    savingIds.value.add(item.category_id);
    savedIds.value.delete(item.category_id);
    try {
        await axios.put(route('plan.upsert'), {
            category_id: item.category_id,
            month: props.month,
            amount,
        });
        // Update the prop-side amount so future comparisons are correct.
        item.amount = amount;
        savedIds.value.add(item.category_id);
        // Fade the "saved" pill after 1.2s.
        setTimeout(() => savedIds.value.delete(item.category_id), 1200);
    } catch (e) {
        // On error, leave the input alone; user can retry by blurring again.
    } finally {
        savingIds.value.delete(item.category_id);
    }
};

// Month nav — keep the URL as the source of truth so a bookmark or a
// refresh reproduces the same state.
const goToMonth = (isoDate: string) => {
    router.get(route('plan.index'), { month: isoDate }, { preserveScroll: true });
};

const prevMonth = () => {
    const d = subMonths(startOfMonth(parseISO(props.month)), 1);
    goToMonth(format(d, 'yyyy-MM-dd'));
};

const nextMonth = () => {
    const d = addMonths(startOfMonth(parseISO(props.month)), 1);
    goToMonth(format(d, 'yyyy-MM-dd'));
};

const isCurrentMonth = computed(() => {
    return props.month === format(startOfMonth(new Date()), 'yyyy-MM-dd');
});

const copyFromPrevious = () => {
    router.post(route('plan.copy'), { month: props.month }, {
        preserveScroll: true,
    });
};

const hasAnyIntent = computed(() => {
    return props.groups.some((g) => g.items.some((i) => i.amount > 0));
});
</script>

<template>
    <AppLayout :title="$t('Spending plan')">
        <main class="px-4 mx-auto mt-5 mb-10 max-w-screen-lg sm:px-6 lg:px-8 space-y-4">
            <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-body">{{ $t('Spending plan') }}</h1>
                    <p class="text-sm text-body-1/70">
                        {{ $t('Set what you intend to spend on each category this month. Independent of reconciliation — plan first, cuadra después.') }}
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        type="button"
                        class="p-2 rounded-md border border-base hover:border-primary/40 text-body-1/70 hover:text-primary transition"
                        :title="$t('Previous month')"
                        @click="prevMonth"
                    >
                        <IMdiChevronLeft />
                    </button>
                    <span class="min-w-[140px] text-center font-semibold text-body">{{ monthLabel }}</span>
                    <button
                        type="button"
                        class="p-2 rounded-md border border-base hover:border-primary/40 text-body-1/70 hover:text-primary transition"
                        :title="$t('Next month')"
                        @click="nextMonth"
                    >
                        <IMdiChevronRight />
                    </button>
                    <LogerButton variant="neutral" @click="copyFromPrevious" :title="$t('Copy from previous month')">
                        <IMdiContentCopy class="mr-1" />
                        {{ $t('Copy from last month') }}
                    </LogerButton>
                </div>
            </header>

            <!-- Grand total hero — the anchor number for the whole page. -->
            <section class="bg-base-lvl-3 rounded-lg border border-base p-5 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                        {{ $t('Total intended for') }} {{ monthLabel }}
                    </p>
                    <p class="text-3xl font-bold text-body tabular-nums mt-1">{{ formatMoney(localGrandTotal) }}</p>
                </div>
                <div class="text-sm text-body-1/60 max-w-xs">
                    {{ $t('Type an amount in each row. Saves automatically when you tab out.') }}
                </div>
            </section>

            <!-- Empty state (no categories at all) -->
            <section v-if="!groups.length" class="bg-base-lvl-3 rounded-lg border border-base p-8 text-center">
                <div class="text-4xl mb-2">📋</div>
                <p class="text-body-1">{{ $t('No categories yet. Create some in the Budget page first.') }}</p>
                <Link href="/budgets" class="text-primary hover:underline text-sm mt-2 inline-block">
                    {{ $t('Open Budget') }} →
                </Link>
            </section>

            <!-- Groups -->
            <section
                v-for="group in groups"
                :key="group.group_id"
                class="bg-base-lvl-3 rounded-lg border border-base overflow-hidden"
            >
                <header class="flex items-center justify-between px-5 py-3 bg-base-lvl-2 border-b border-base">
                    <h2 class="text-sm font-semibold text-body uppercase tracking-wide">
                        {{ group.group_name }}
                    </h2>
                    <p class="text-sm font-bold tabular-nums text-body">
                        {{ formatMoney(localGroupTotals[group.group_id] ?? 0) }}
                    </p>
                </header>
                <div v-if="!group.items.length" class="px-5 py-3 text-sm text-body-1/60">
                    {{ $t('No subcategories in this group.') }}
                </div>
                <ul v-else class="divide-y divide-base">
                    <li
                        v-for="item in group.items"
                        :key="item.category_id"
                        class="flex items-center gap-3 px-5 py-2.5 hover:bg-base-lvl-2 transition"
                    >
                        <span class="flex-1 text-sm text-body truncate">{{ item.name }}</span>
                        <span
                            v-if="savingIds.has(item.category_id)"
                            class="text-[10px] uppercase tracking-wide text-body-1/50"
                        >
                            {{ $t('Saving…') }}
                        </span>
                        <span
                            v-else-if="savedIds.has(item.category_id)"
                            class="inline-flex items-center gap-1 text-[10px] uppercase tracking-wide text-emerald-600"
                        >
                            <IMdiCheck class="w-3 h-3" /> {{ $t('Saved') }}
                        </span>
                        <div class="relative">
                            <input
                                v-model="amounts[item.category_id]"
                                type="number"
                                inputmode="decimal"
                                min="0"
                                step="0.01"
                                :placeholder="'0.00'"
                                class="w-32 px-3 py-1.5 text-right rounded-md border border-base bg-base-lvl-1 text-body tabular-nums focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50"
                                @blur="saveIntent(item)"
                                @keydown.enter.prevent="($event.target as HTMLInputElement)?.blur()"
                            />
                        </div>
                    </li>
                </ul>
            </section>

            <p v-if="hasAnyIntent" class="text-xs text-body-1/50 text-center pt-2">
                {{ $t('This plan is independent from your Budget targets and your actual verified transactions. It lives in its own table (`spending_intents`).') }}
            </p>
        </main>
    </AppLayout>
</template>

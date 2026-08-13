<script setup lang="ts">
import { computed, ref, toRefs } from "vue";
import { router } from "@inertiajs/vue3";

import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";
import BudgetProgress from "@/domains/budget/components/BudgetProgress.vue";
import NextPaymentsWidget from "@/domains/transactions/components/NextPaymentsWidget.vue";
import AccountBalancesWidget from "./AccountBalancesWidget.vue";
import OccurrenceWidget from "@/domains/housing/components/OccurrenceWidget.vue";
import MealWidget from "@/domains/meal/components/MealWidget.vue";
import WatchlistDashboardWidget from "@/domains/watchlist/components/WatchlistDashboardWidget.vue";
import DueTodayWidget, { type TodayItem } from "./DueTodayWidget.vue";
import RoutineNowNextWidget from "./RoutineNowNextWidget.vue";

import { useNetWorth, INetWorthEntry } from "@/domains/transactions/useNetWorth";
import { formatMoney, getDayDiff } from "@/utils";
import { IAccount, ITransaction } from "@/domains/transactions/models";
import { IBudgetStat } from "@/domains/budget/models/budget";
import { IOccurrenceCheck } from "@/domains/housing/models";

const props = defineProps<{
    netWorth: INetWorthEntry[];
    expenses: number | string;
    accounts: IAccount[];
    budgetTotal: IBudgetStat[];
    nextPayments: ITransaction[];
    checks: IOccurrenceCheck[];
    meals: { data: any[] };
    user: { name: string; current_team_id: number };
    topWatchlists: any[];
    isMealsEnabled: boolean;
    isHousingEnabled: boolean;
    todayItems?: TodayItem[];
    drafts?: number;
}>();

const { netWorth } = toRefs(props);
const { thisMonth, lastMonth, monthMovement, monthMovementVariance } = useNetWorth(netWorth);

// Coerce to Number because the API sends totals as strings (e.g. "0.00").
// Without this, `!"0.00"` evaluates to `false` (non-empty string is truthy),
// the divide-by-zero guard below is bypassed, and the card renders
// "Infinity% spent" — a real regression seen on first-load-without-budget.
const currentBudget = computed(() => ({
    total: Number(props.budgetTotal?.at(-1)?.total ?? 0),
    spending: Number(props.budgetTotal?.at(-1)?.spending ?? 0),
    savings: Number(props.budgetTotal?.at(-1)?.savings ?? 0),
}));

const hasBudget = computed(() => Number.isFinite(currentBudget.value.total) && currentBudget.value.total > 0);

const spentPercentage = computed(() => {
    if (!hasBudget.value) return 0;
    return Math.round((currentBudget.value.spending / currentBudget.value.total) * 100);
});

// balance arrives from the server as a string; `+` would coerce to concat.
const numericBalance = (a: IAccount): number => {
    const value = parseFloat(String(a.balance ?? 0));
    return Number.isFinite(value) ? value : 0;
};

const totalBalance = computed(() => {
    return props.accounts?.reduce((sum, a) => sum + numericBalance(a), 0) ?? 0;
});

const creditCardDebt = computed(() => {
    return props.accounts
        ?.filter(a => a.credit_limit && a.credit_limit > 0)
        ?.reduce((sum, a) => sum + Math.abs(numericBalance(a)), 0) ?? 0;
});

const movementIsPositive = computed(() => Number(monthMovement.value) >= 0);

// ---------------------------------------------------------------------------
// "What needs your attention today" hero.
// Aggregates the genuinely time-sensitive signals scattered across the widgets
// below — overdue/upcoming payments, overdue recurring reminders and drafts
// waiting for review — so the real urgency isn't buried among equal-weight
// cards. Everything here is derived from props already passed to the widgets;
// no new server data is required.
// ---------------------------------------------------------------------------

// Whole-day diff from today. Negative => in the past (overdue), 0 => today,
// positive => in the future.
const daysFromToday = (dateStr?: string | null): number | null => {
    if (!dateStr) return null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const target = new Date(dateStr);
    if (Number.isNaN(target.getTime())) return null;
    target.setHours(0, 0, 0, 0);
    return Math.round((target.getTime() - today.getTime()) / 86400000);
};

const overduePayments = computed(() =>
    (props.nextPayments ?? []).filter(p => {
        const d = daysFromToday((p as any).date);
        return d !== null && d < 0;
    })
);

const dueSoonPayments = computed(() =>
    (props.nextPayments ?? []).filter(p => {
        const d = daysFromToday((p as any).date);
        return d !== null && d >= 0 && d <= 7;
    })
);

const overduePaymentsTotal = computed(() =>
    overduePayments.value.reduce((sum, p) => sum + Number((p as any).total ?? 0), 0)
);

// Reminders past their usual cadence (avg + 3d), mirrors OccurrenceWidget's
// "overdue" threshold so the hero and the widget agree.
const overdueReminders = computed(() =>
    (props.checks ?? []).filter(c => {
        const avg = c.avg_days_passed;
        if (!avg || avg <= 0) return false;
        const days = getDayDiff(c.last_date);
        return typeof days === "number" && days >= avg + 3;
    })
);

const draftsCount = computed(() => Number(props.drafts ?? 0));

// The hero shouts (red) only when something is genuinely overdue.
const hasUrgent = computed(() =>
    overduePayments.value.length > 0 || overdueReminders.value.length > 0
);

const hasAttention = computed(() =>
    hasUrgent.value || dueSoonPayments.value.length > 0 || draftsCount.value > 0
);

// ---------------------------------------------------------------------------
// Next payments density — show the top 3 by default with a "see all" toggle so
// the list stops dominating half the screen.
// ---------------------------------------------------------------------------
const showAllPayments = ref(false);
const visiblePayments = computed(() =>
    showAllPayments.value ? props.nextPayments : (props.nextPayments ?? []).slice(0, 3)
);
</script>

<template>
    <div class="space-y-4">
        <!-- Hero: what needs your attention today. Prominent block at the very
             top so overdue/upcoming items and drafts to review read as the
             primary signal; the rest of the dashboard is demoted below it. -->
        <section
            class="rounded-xl border p-5"
            :class="hasUrgent
                ? 'border-error/40 bg-error/5'
                : 'border-base bg-base-lvl-3'"
        >
            <h2 class="text-sm font-bold text-body flex items-center gap-2">
                <i
                    class="fa"
                    :class="hasUrgent ? 'fa-triangle-exclamation text-error' : 'fa-circle-check text-success'"
                />
                {{ $t('What needs your attention today') }}
            </h2>

            <div v-if="hasAttention" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                <!-- Overdue payments -->
                <button
                    v-if="overduePayments.length"
                    type="button"
                    class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg bg-base-lvl-2 border border-error/30 hover:border-error/50 transition text-left"
                    @click="router.visit('/finance/transactions')"
                >
                    <span class="flex items-center gap-2 min-w-0">
                        <i class="fa fa-clock text-error flex-shrink-0" />
                        <span class="text-sm font-semibold text-body truncate">
                            {{ overduePayments.length }} {{ $t('overdue payments') }}
                        </span>
                    </span>
                    <span class="text-sm font-bold text-error tabular-nums flex-shrink-0">
                        {{ formatMoney(overduePaymentsTotal) }}
                    </span>
                </button>

                <!-- Overdue reminders -->
                <button
                    v-if="overdueReminders.length"
                    type="button"
                    class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg bg-base-lvl-2 border border-error/30 hover:border-error/50 transition text-left"
                    @click="router.visit('/housing/occurrence')"
                >
                    <span class="flex items-center gap-2 min-w-0">
                        <i class="fa fa-bell text-error flex-shrink-0" />
                        <span class="text-sm font-semibold text-body truncate">
                            {{ overdueReminders.length }} {{ $t('overdue reminders') }}
                        </span>
                    </span>
                    <span class="text-xs font-semibold text-error flex-shrink-0">{{ $t('Review') }} →</span>
                </button>

                <!-- Upcoming payments (next 7 days) — informational, not red -->
                <button
                    v-if="dueSoonPayments.length"
                    type="button"
                    class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg bg-base-lvl-2 border border-base hover:border-primary/30 transition text-left"
                    @click="router.visit('/finance/transactions')"
                >
                    <span class="flex items-center gap-2 min-w-0">
                        <i class="fa fa-calendar-day text-body-1/60 flex-shrink-0" />
                        <span class="text-sm font-semibold text-body truncate">
                            {{ dueSoonPayments.length }} {{ $t('payments due soon') }}
                        </span>
                    </span>
                    <span class="text-xs text-body-1/50 flex-shrink-0">{{ $t('next 7 days') }}</span>
                </button>

                <!-- Drafts / transactions to review -->
                <button
                    v-if="draftsCount"
                    type="button"
                    class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg bg-base-lvl-2 border border-base hover:border-primary/30 transition text-left"
                    @click="router.visit('/inbox')"
                >
                    <span class="flex items-center gap-2 min-w-0">
                        <i class="fa fa-receipt text-primary flex-shrink-0" />
                        <span class="text-sm font-semibold text-body truncate">
                            {{ draftsCount }} {{ $t('transactions to review') }}
                        </span>
                    </span>
                    <span class="text-xs font-semibold text-primary flex-shrink-0">{{ $t('Review') }} →</span>
                </button>
            </div>

            <p v-else class="mt-2 text-sm text-body-1/70">
                {{ $t('Nothing needs your attention right now. You are all caught up.') }}
            </p>
        </section>

        <!-- Secondary stats row (demoted below the hero). -->
        <section class="grid grid-cols-2 md:grid-cols-3 gap-3">

            <button
                class="bg-base-lvl-3 rounded-lg p-4 text-left border border-base hover:border-primary/30 transition cursor-pointer"
                @click="router.visit('/finance/transactions')"
            >
                <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Current Expenses') }}</p>
                <p class="text-lg font-bold text-body mt-1">
                    <MoneyPresenter :value="expenses" />
                </p>
            </button>

            <button
                class="bg-base-lvl-3 rounded-lg p-4 text-left border border-base hover:border-primary/30 transition cursor-pointer"
                @click="router.visit('/finance/transactions')"
            >
                <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Total Balance') }}</p>
                <p class="text-lg font-bold mt-1" :class="totalBalance >= 0 ? 'text-body' : 'text-error'">
                    <MoneyPresenter :value="totalBalance" />
                </p>
            </button>

            <button
                class="bg-base-lvl-3 rounded-lg p-4 text-left border border-base hover:border-primary/30 transition cursor-pointer"
                @click="router.visit('/budgets')"
            >
                <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Budget') }}</p>
                <template v-if="hasBudget">
                    <p class="text-lg font-bold text-body mt-1">{{ spentPercentage }}%
                        <span class="text-xs font-normal text-body-1/50">{{ $t('spent') }}</span>
                    </p>
                    <div class="mt-2">
                        <BudgetProgress
                            class="h-1.5 rounded-full"
                            :goal="currentBudget.total"
                            :current="currentBudget.spending"
                            :progress-class="['bg-primary', 'bg-base-lvl-1']"
                            :show-labels="false"
                        />
                    </div>
                </template>
                <template v-else>
                    <p class="text-sm font-semibold text-body mt-1">{{ $t('No budget set') }}</p>
                    <p class="text-xs text-primary mt-1">{{ $t('Set your first budget') }} →</p>
                </template>
            </button>
        </section>

        <!-- Main content: 2 columns -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Left column: accounts + action items -->
            <div class="md:col-span-2 space-y-4">
                <!-- Now / Next from the weekly routine -->
                <RoutineNowNextWidget />
                <!-- Today / needs attention -->
                <DueTodayWidget v-if="todayItems?.length" :items="todayItems" />

                <!-- Accounts -->
                <AccountBalancesWidget :accounts="accounts" />

                <!-- Next payments — only if there are any. Shows the top 3 with a
                     "see all" toggle so the list doesn't dominate the screen. -->
                <div v-if="nextPayments?.length" class="bg-base-lvl-3 rounded-lg border border-base">
                    <NextPaymentsWidget :payments="visiblePayments" class="px-4" />
                    <button
                        v-if="nextPayments.length > 3"
                        type="button"
                        class="w-full text-center text-xs font-semibold text-primary hover:underline py-2.5 border-t border-base"
                        @click="showAllPayments = !showAllPayments"
                    >
                        <template v-if="showAllPayments">{{ $t('Show less') }}</template>
                        <template v-else>{{ $t('See all') }} ({{ nextPayments.length }})</template>
                    </button>
                </div>
            </div>

            <!-- Right column: secondary modules -->
            <div class="space-y-4">
                <!-- Occurrences -->
                <OccurrenceWidget
                    v-if="isHousingEnabled && checks?.length"
                    :checks="checks"
                    :wrap="true"
                />

                <!-- Today's meals -->
                <MealWidget
                    v-if="isMealsEnabled"
                    :meals="meals?.data ?? []"
                />

                <!-- Total credit-card debt callout. This is the SUM of every card's
                     debt — distinct from the per-card balance shown under "Accounts".
                     Debt is a normal state, so it stays neutral (not red) unless the
                     user is actually behind on a payment (surfaced in the hero). -->
                <div
                    v-if="creditCardDebt > 0"
                    class="bg-base-lvl-3 rounded-lg border border-base p-4 cursor-pointer hover:border-primary/30 transition"
                    @click="router.visit('/finance/transactions')"
                >
                    <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Total Credit Card Debt') }}</p>
                    <p class="text-lg font-bold text-body mt-1">
                        <MoneyPresenter :value="creditCardDebt" />
                    </p>
                    <p class="text-[11px] text-body-1/50 mt-0.5">{{ $t('Across all your cards') }}</p>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, toRefs } from "vue";
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
import { formatMoney } from "@/utils";
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
</script>

<template>
    <div class="space-y-4">
        <!-- Hero stats row -->
        <section class="grid grid-cols-2 md:grid-cols-3 gap-3">

            <button
                class="bg-base-lvl-3 rounded-lg p-4 text-left border border-base hover:border-primary/30 transition cursor-pointer"
                @click="router.visit('/finance/transactions')"
            >
                <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Current Expenses') }}</p>
                <p class="text-xl font-bold text-red-400 mt-1">
                    <MoneyPresenter :value="expenses" />
                </p>
            </button>

            <button
                class="bg-base-lvl-3 rounded-lg p-4 text-left border border-base hover:border-primary/30 transition cursor-pointer"
                @click="router.visit('/finance/transactions')"
            >
                <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Total Balance') }}</p>
                <p class="text-xl font-bold mt-1" :class="totalBalance >= 0 ? 'text-green-500' : 'text-red-400'">
                    <MoneyPresenter :value="totalBalance" />
                </p>
            </button>

            <button
                class="bg-base-lvl-3 rounded-lg p-4 text-left border border-base hover:border-primary/30 transition cursor-pointer"
                @click="router.visit('/budgets')"
            >
                <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Budget') }}</p>
                <template v-if="hasBudget">
                    <p class="text-xl font-bold text-body mt-1">{{ spentPercentage }}%
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
                <button
                    v-if="drafts"
                    type="button"
                    class="w-full text-left bg-base-lvl-3 rounded-lg border border-base p-4 flex items-center gap-3 hover:border-primary/30 transition"
                    @click="router.visit('/finance/transactions')"
                >
                    <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10 text-primary flex-shrink-0">
                        <i class="fas fa-receipt" />
                    </span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm font-semibold text-body-1">{{ drafts }} {{ $t('transactions to review') }}</span>
                        <span class="block text-xs text-body-1/60">{{ $t('Imported, waiting for your approval') }}</span>
                    </span>
                    <span class="text-xs font-semibold text-primary">{{ $t('Review') }} →</span>
                </button>

                <!-- Accounts -->
                <AccountBalancesWidget :accounts="accounts" />

                <!-- Next payments — only if there are any -->
                <div v-if="nextPayments?.length" class="bg-base-lvl-3 rounded-lg border border-base">
                    <NextPaymentsWidget :payments="nextPayments" class="px-4" />
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

                <!-- Credit card debt callout -->
                <div
                    v-if="creditCardDebt > 0"
                    class="bg-base-lvl-3 rounded-lg border border-base p-4 cursor-pointer hover:border-error/30 transition"
                    @click="router.visit('/finance/transactions')"
                >
                    <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Credit Card Debt') }}</p>
                    <p class="text-lg font-bold text-red-400 mt-1">
                        <MoneyPresenter :value="creditCardDebt" />
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>

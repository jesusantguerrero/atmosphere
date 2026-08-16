<script setup lang="ts">
import { computed, toRefs } from "vue";
import { router } from "@inertiajs/vue3";


import { AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import BackgroundCard from "@/Components/molecules/BackgroundCard.vue";

import { useTrendOptions } from "./Partials/trendOptions";
const trendOptions = useTrendOptions();
import TrendTemplate from "./Partials/TrendTemplate.vue";
import TrendSectionNav from "./Partials/TrendSectionNav.vue";
import ChartTopCreditCard from "@/Components//ChartTopCreditCard.vue";


import { useServerSearch } from "@/composables/useServerSearch";
import AccountFilters from "@/domains/transactions/components/AccountFilters.vue";
import CreditCardRewardsWidget from "@/domains/transactions/components/CreditCardRewardsWidget.vue";
import { formatMoney } from "@/utils";
import { formatMonth } from "@/utils";

const props = withDefaults(defineProps<{
    user: Record<string, any>;
    data: {
        hasCreditCards: boolean;
        lastCycleBalances: any[];
        creditTotal: number;
        creditTotalPrevious: number;
        creditTotalDelta: number;
        creditCapacity: number;
        creditLineUsage: number;
        billingCyclesByCard: {
            data: any[];
            discountTotal: number;
            total: number;
            subtotal: number;
        };
        topCategoriesByCard: any[];
        topPayeesByCard: any[];
    };
   metaData: Record<string, any>;
   serverSearchOptions: Record<string, any>;
    section: string;
}>(), ({
  serverSearchOptions: () => ({}),
}));

const { serverSearchOptions } = toRefs(props);
const {state: pageState, executeSearchWithDelay } = useServerSearch(serverSearchOptions, {
    manual: true
});

const usageColorClass = computed(() => {
    const usage = props.data.creditLineUsage ?? 0;
    if (usage >= 70) return 'text-red-500';
    if (usage >= 30) return 'text-amber-500';
    return 'text-green-500';
});

const hasCards = computed(() => props.data.hasCreditCards === true);

function cardUsageClass(used: number, limit: number): string {
    if (!limit || limit <= 0) return 'bg-gray-300';
    const pct = (used / limit) * 100;
    if (pct >= 70) return 'bg-red-500';
    if (pct >= 30) return 'bg-amber-500';
    return 'bg-green-500';
}

function cardUsagePct(used: number, limit: number): number {
    if (!limit || limit <= 0) return 0;
    return Math.min(100, (used / limit) * 100);
}

const deltaDisplay = computed(() => {
    const delta = props.data.creditTotalDelta ?? 0;
    const prev = props.data.creditTotalPrevious ?? 0;
    if (prev === 0) return null;
    const sign = delta > 0 ? '+' : delta < 0 ? '-' : '';
    const color = delta > 0 ? 'text-red-500' : delta < 0 ? 'text-green-500' : 'text-body-1/50';
    return { sign, color, formatted: formatMoney(Math.abs(delta)) };
});
</script>

<template>
  <AppLayout :title="metaData.title">
    <template #header>
      <TrendSectionNav :sections="trendOptions">
        <template #actions>
                <AtDatePager
                    class="w-full h-12 border-none bg-base-lvl-1 text-body"
                    v-model:startDate="pageState.dates.startDate"
                    v-model:endDate="pageState.dates.endDate"
                    @change="executeSearchWithDelay(500)"
                    controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
                    next-mode="month">
            {{ formatMonth(pageState.dates.startDate, 'MMMM yyyy') }}
        </AtDatePager>
                <AccountFilters
                    class="w-full"
                    v-model:accounts="pageState.filters.account"
                    v-model:categories="pageState.filters.category"
                />
        </template>
      </TrendSectionNav>
    </template>

    <TrendTemplate title="Finance" ref="financeTemplateRef" :hide-panel="true">
        <section
            v-if="!hasCards"
            class="bg-base-lvl-3 rounded-lg border border-base px-6 py-16 flex flex-col items-center justify-center text-center"
        >
            <div class="text-4xl mb-3 opacity-40">💳</div>
            <h2 class="text-lg font-bold text-body mb-1">No credit cards yet</h2>
            <p class="text-sm text-body-1/60 mb-5 max-w-sm">
                Add a credit card account to see utilization, per-cycle spend, and category breakdowns.
            </p>
            <a href="/finance/accounts/create?type=credit_card" class="px-4 py-2 text-sm font-medium rounded bg-primary text-white hover:opacity-90 transition">
                Add a credit card
            </a>
        </section>

        <article v-else>
            <header class="grid grid-cols-2 md:grid-cols-4 gap-5">
                <BackgroundCard
                    class="w-full cursor-pointer text-body-1 bg-base-lvl-3"
                    :value="data.lastCycleBalances?.length ?? 0"
                    :label="'Credits Qty'"
                    label-class="capitalize text-secondary font-base"
                />
                <BackgroundCard
                    class="w-full cursor-pointer text-body-1 bg-base-lvl-3"
                    :value="formatMoney(data.creditCapacity)"
                    :label="'Total Capacity'"
                    label-class="capitalize text-secondary font-base"
                />
                <BackgroundCard
                    class="w-full cursor-pointer text-body-1 bg-base-lvl-3"
                    :value="formatMoney(data.creditTotal)"
                    :label="'Credit Total'"
                    label-class="capitalize text-secondary font-base"
                >
                    <template #value>
                        <div class="flex items-baseline justify-center gap-2">
                            <span>{{ formatMoney(data.creditTotal) }}</span>
                            <span v-if="deltaDisplay" :class="deltaDisplay.color" class="text-xs font-semibold">
                                {{ deltaDisplay.sign }}{{ deltaDisplay.formatted }}
                            </span>
                        </div>
                    </template>
                </BackgroundCard>
                <BackgroundCard
                    class="w-full cursor-pointer text-body-1 bg-base-lvl-3"
                    :value="`${data.creditLineUsage}%`"
                    :label="'Credit Line Usage'"
                    :text-class="`text-2xl font-bold ${usageColorClass}`"
                    label-class="capitalize text-secondary font-base"
                />
            </header>
            <section class="w-full mt-4 bg-base-lvl-3 border border-base rounded-lg overflow-hidden">
                <div class="px-5 py-3 border-b border-base">
                    <h2 class="font-bold text-body text-sm">{{ $t('Credit card expenses') }}</h2>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs uppercase text-body-1/50 bg-base-lvl-2">
                            <th class="text-left px-5 py-2 font-medium">Card</th>
                            <th class="text-right px-5 py-2 font-medium">Balance</th>
                            <th class="text-right px-5 py-2 font-medium">Limit</th>
                            <th class="text-left px-5 py-2 font-medium w-1/3">Utilization</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="card in data.lastCycleBalances"
                            :key="card.id"
                            class="border-b border-base hover:bg-base-lvl-1/50 transition cursor-pointer"
                            @click="router.visit(`/finance/accounts/${card.id}`)"
                        >
                            <td class="px-5 py-2 text-body">{{ card.name }}</td>
                            <td class="px-5 py-2 text-right tabular-nums">{{ formatMoney(card.total) }}</td>
                            <td class="px-5 py-2 text-right text-body-1/60 tabular-nums">{{ formatMoney(card.credit_limit) }}</td>
                            <td class="px-5 py-2">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-base-lvl-1 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="cardUsageClass(card.total, card.credit_limit)"
                                            :style="{ width: `${cardUsagePct(card.total, card.credit_limit)}%` }"
                                        />
                                    </div>
                                    <span class="text-xs tabular-nums text-body-1/60 w-12 text-right">
                                        {{ cardUsagePct(card.total, card.credit_limit).toFixed(1) }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="mt-4 flex flex-col lg:flex-row gap-4">
                <section class="w-full lg:w-4/12 space-y-2">
                    <div
                        v-if="data.billingCyclesByCard?.discountTotal"
                        class="bg-base-lvl-3 border border-base rounded-lg px-4 py-3 flex items-baseline justify-between"
                    >
                        <span class="text-xs uppercase tracking-wide text-body-1/60">Rewards earned</span>
                        <span class="text-lg font-bold text-green-500 tabular-nums">
                            {{ formatMoney(data.billingCyclesByCard.discountTotal) }}
                        </span>
                    </div>
                    <CreditCardRewardsWidget
                        :legend="true"
                        :credit-card-data="data.billingCyclesByCard.data"
                    />
                </section>
                <section class="w-full lg:w-8/12">
                    <ChartTopCreditCard
                        class="bg-base-lvl-3 rounded-md overflow-hidden"
                        group-name="name"
                        :data="data.topCategoriesByCard"
                    />
                </section>
            </section>
            <section class="w-full mt-4">
                <ChartTopCreditCard
                    class="bg-base-lvl-3 rounded-md overflow-hidden"
                    :data="data.topPayeesByCard"
                />
            </section>
        </article>
    </TrendTemplate>
  </AppLayout>
</template>

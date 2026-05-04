<script setup lang="ts">
import { ref, toRefs, computed } from "vue";
// @ts-ignore
import { AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import ChartComparison from "@/Components/widgets/ChartComparison.vue";
import WidgetStats from "@/Components/widgets/WidgetStats.vue";

import FinanceTemplate from "./Partials/FinanceTemplate.vue";
import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";

import TransactionsList from "@/domains/transactions/components/TransactionsList.vue";
import WatchlistModal from "@/domains/watchlist/components/WatchlistModal.vue";
import WatchlistSummaryCard from "@/domains/watchlist/components/WatchlistSummaryCard.vue";

import { useServerSearch } from "@/composables/useServerSearch";
import { formatMoney, formatMonth, MonthTypeFormat } from "@/utils";
import { router } from "@inertiajs/vue3";

import { IServerSearchData } from "@/composables/useServerSearchV2";
import { WatchlistResource } from "@/domains/watchlist/models";

const props = withDefaults(defineProps<{
  user: Object,
  resource: WatchlistResource,
  serverSearchOptions: IServerSearchData,
  watchlist: Record<string, string>[]
}>(), {

});
const { serverSearchOptions } = toRefs(props);
const { state: pageState, executeSearchWithDelay } = useServerSearch(serverSearchOptions, {
    manual: true,
    defaultDates: true
});

const isModalOpen = ref(false);
const resourceToEdit = ref(null);

const sectionName = computed(() => {
    return  `${props.resource.name} : ${formatMonth(pageState?.dates.startDate, MonthTypeFormat.long)}`
})

const statCards = computed(() => [
    {
        value: props.resource.month.transactionsCount,
        label: 'transactions'
    }, {
        value: props.resource.month.lastTransactionDate,
        label: 'date'
    }, {
        value: formatMoney(props.resource.prevMonth.total, props.resource.prevMonth.currency_code),
        label: "last month"
    }
]);

const target = computed(() => Number(props.resource.target ?? 0));
const monthTotal = computed(() => Number(props.resource.month?.total ?? 0));
const monthProjected = computed(() => Number(props.resource.month?.projected ?? 0));
const isCurrentPeriod = computed(() => Boolean(props.resource.month?.is_current_period));
const daysElapsed = computed(() => Number(props.resource.month?.days_elapsed ?? 0));
const daysInPeriod = computed(() => Number(props.resource.month?.days_in_period ?? 0));

const hasTarget = computed(() => target.value > 0);
const targetRatio = computed(() => hasTarget.value ? monthTotal.value / target.value : 0);
const projectedRatio = computed(() => hasTarget.value ? monthProjected.value / target.value : 0);

const trafficLight = computed(() => {
    if (!hasTarget.value) return null;
    if (targetRatio.value > 1) return { label: 'Over target', bar: 'bg-error', chip: 'bg-error/10 text-error border-error' };
    if (targetRatio.value > 0.7) return { label: 'Close to target', bar: 'bg-warning', chip: 'bg-warning/10 text-warning border-warning' };
    return { label: 'On track', bar: 'bg-success', chip: 'bg-success/10 text-success border-success' };
});

const projectionTone = computed(() => {
    if (!hasTarget.value || !isCurrentPeriod.value) return 'text-body-1';
    if (projectedRatio.value > 1) return 'text-error font-bold';
    if (projectedRatio.value > 0.7) return 'text-warning font-bold';
    return 'text-success font-bold';
});


const parser = (transaction: Record<string, string>) => ({
        title: transaction.description,
        subtitle: `${transaction?.account_from?.name} -> ${transaction.cat_name}`,
        date: transaction.date,
        value: transaction.amount,
        currencyCode: transaction.currency_code,
        status: transaction.status,
});

const transactions = computed(() => {
    const data = Object.values(props.resource.transactions).reduce((allData, val) => {
        allData.push(...val?.data);
        return allData;
    }, []);

    return data.map(parser);
})

const onClick = (itemId: number) => {
    if (props.resource.id == itemId) return
    router.visit(`/finance/watchlist/${itemId}?${location.search}`)
}

const categories = computed(() => {
    return props.resource.transactions;
})
</script>

<template>
  <AppLayout :title="sectionName">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <AtDatePager
            class="w-full h-12 border-none bg-base-lvl-1 text-body"
            v-model:startDate="pageState.dates.startDate"
            v-model:endDate="pageState.dates.endDate"
            controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
            next-mode="month"
            @change="executeSearchWithDelay(5)"
          >
          </AtDatePager>
          <div>
            <LogerButton variant="inverse" @click="isModalOpen=!isModalOpen"> Add WatchList </LogerButton>
          </div>
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate  ref="financeTemplateRef">
      <article class="w-full">

        <section>
            <section class="w-full">
                <WidgetStats
                  class="w-full"
                  :total="formatMoney(resource.month.total, resource.month.currency_code)"
                  description="This month"
                  :cards="statCards"
                >
                 <template #icon>
                  <IIcRoundQueryStats />
                 </template>
                </WidgetStats>
            </section>

            <section v-if="hasTarget" class="bg-base-lvl-3 rounded-md p-4 mt-4 border" :class="trafficLight.chip">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <h4 class="font-bold text-body">Monthly target</h4>
                        <p class="text-xs text-body-1">{{ trafficLight.label }} — {{ Math.round(targetRatio * 100) }}% del límite</p>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold">{{ formatMoney(monthTotal) }}</div>
                        <div class="text-xs text-body-1">de {{ formatMoney(target) }}</div>
                    </div>
                </div>
                <div class="h-2 rounded-full bg-base-lvl-2 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all"
                        :class="trafficLight.bar"
                        :style="{ width: Math.min(100, targetRatio * 100) + '%' }"
                    />
                </div>
                <div v-if="isCurrentPeriod" class="mt-3 pt-3 border-t border-base/40 flex items-baseline justify-between text-sm">
                    <div>
                        <span class="text-body-1">Proyección fin de mes</span>
                        <span class="text-xs text-body-1 ml-2">(día {{ daysElapsed }} de {{ daysInPeriod }})</span>
                    </div>
                    <div class="text-right">
                        <span :class="projectionTone">{{ formatMoney(monthProjected) }}</span>
                        <span class="text-xs text-body-1 ml-1">({{ Math.round(projectedRatio * 100) }}%)</span>
                    </div>
                </div>
            </section>

            <ChartComparison
                class="w-full mb-10 mt-4 overflow-hidden bg-white rounded-lg"
                :title="`${resource.name} Report`"
                ref="ComparisonRevenue"
                :data="categories"
                data-item-total="total_amount"
            />

        </section>

        <TransactionsList
            class="w-full"
            table-class="overflow-auto text-sm"
            :transactions="transactions"
        />

      </article>

      <WatchlistModal
        v-if="isModalOpen"
        v-model:show="isModalOpen"
        :form-data="resourceToEdit"
      />

      <template #panel>
        <section class="grid lg:grid-cols-1 gap-2 pt-4">
            <WatchlistSummaryCard
                v-for="item in watchlist"
                :startDate="pageState.dates.startDate"
                :item="item"
                @click="onClick(item.id)"
                class="w-full"
            />
        </section>
      </template>
    </FinanceTemplate>
  </AppLayout>
</template>



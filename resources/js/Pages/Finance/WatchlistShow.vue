<script setup lang="ts">
import { ref, toRefs, computed } from "vue";
// @ts-ignore
import { AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import ChartComparison from "@/Components/widgets/ChartComparison.vue";

import FinanceTemplate from "./Partials/FinanceTemplate.vue";
import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";

import TransactionsList from "@/domains/transactions/components/TransactionsList.vue";
import WatchlistModal from "@/domains/watchlist/components/WatchlistModal.vue";
import WatchlistSummaryCard from "@/domains/watchlist/components/WatchlistSummaryCard.vue";

import { useServerSearch } from "@/composables/useServerSearch";
import { formatMoney, formatMonth, MonthTypeFormat } from "@/utils";
import { IAccount } from "@/domains/transactions/models";
import { IServerSearchData } from "@/composables/useServerSearchV2";
import { router } from "@inertiajs/vue3";

interface MonthStat {
    total: number,
    currency_code: string,
    lastTransactionDate: string,
    transactionsCount: number
}

interface WatchlistResource {
    month: MonthStat;
    prevMonth: MonthStat;
    transactions: Record<string, {
        data: Record<string, string>[]
    }>
}

const props = withDefaults(defineProps<{
  user: Object,
  resource: WatchlistResource,
  categories: Record<string, string>[],
  accounts: IAccount[],
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

const parser = (transactions: Record<string, string>[]) => {
    const data = Object.values(transactions).reduce((allData, val) => {
        console.log(val);
        allData.push(...val?.data);
        return allData;
    }, []);

    debugger

    return data.map((transaction) => ({
        title: transaction.description,
        subtitle: `${transaction?.account_from?.name} -> ${transaction.cat_name}`,
        date: transaction.date,
        value: transaction.amount,
        currencyCode: transaction.currency_code,
        status: transaction.status,
    }));
}

const onClick = (itemId: number) => {
    if (props.resource.id == itemId) return
    router.visit(`/finance/watchlist/${itemId}?${location.search}`)
}

const categories = computed(() => {
    return props.resource.categories;
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

    <FinanceTemplate :accounts="accounts" ref="financeTemplateRef">
      <article class="w-full">

        <section>
            <section class="w-full">
                <WidgetWatchlistStats
                  class="w-full"
                  :total="formatMoney(resource.month.total, resource.month.currency_code)"
                  description="This month"
                  :cards="statCards"
                >
                 <template #icon>
                  <IIcRoundQueryStats />
                 </template>
                </WidgetWatchlistStats>
            </section>
            <!-- <ChartComparison
                class="w-full mb-10 mt-4 overflow-hidden bg-white rounded-lg"
                :title="`${resource.name} Report`"
                ref="ComparisonRevenue"
                :data="categories"
                data-item-total="total_amount"
            /> -->
        </section>

        <TransactionsList
            class="w-full"
            table-class="overflow-auto text-sm"
            :transactions="parser(resource.transactions)"
        />

      </article>

      <WatchlistModal
        v-if="isModalOpen"
        v-model:show="isModalOpen"
        :form-data="resourceToEdit"
      />

      <template #panel>
        <section class="grid grid-cols-2 gap-2 pt-4">
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



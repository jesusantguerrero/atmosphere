<script setup lang="ts">
import { computed, toRefs } from "vue";
import { router } from "@inertiajs/vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";

import TrendTemplate from "./Partials/TrendTemplate.vue";
import ChartNetWorth from "@/Components/ChartNetworth.vue";
import TrendSectionNav from "./Partials/TrendSectionNav.vue";
import WidgetTitleCard from "@/Components/molecules/WidgetTitleCard.vue";


import { useServerSearch } from "@/composables/useServerSearch";
import RangeFilters from "@/domains/transactions/components/RangeFilters.vue";
import { formatMoney } from "@/utils";

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  data: {
    type: Array,
    default() {
      return [];
    },
  },
  metaData: {
    type: Object
  },
  serverSearchOptions: {
    type: Object,
    default: () => ({}),
  },
  section: {
    type: String
  }
});

const { serverSearchOptions } = toRefs(props);
const {state: pageState, executeSearchWithDelay } = useServerSearch(serverSearchOptions, {
    manual: true
});

const handleSelection = (index: number) => {
    const parent: Record<string, string> = props.data[index]
    if (!props.metaData.parent_name) {
        router.visit(`/trends/categories?filter[parent_id]=${parent.id}`)
    }
}

const trends = [
    {
        label: 'Spending',
        url: '/trends'
    },
    {
        label: 'Income',
        url: '/trends/payees'
    },
    {
        label: 'Net Worth',
        url: '/trends/net-worth'
    },
    {
        label: 'Income v Expenses',
        url: '/trends/income-expenses'
    },
    {
        label: 'Income vs Expenses Graph',
        url: '/trends/income-expenses-graph'
    },
    {
        label: 'Year spending',
        url: '/trends/spending-year'
    }
]

const isYearSpending = computed(() => {
    return ['spendingYear'].includes(props.metaData.name)
})


const getEntryBalance = (monthEntry: { assets: number, debts: number }) => {
    return parseFloat(monthEntry.assets) + parseFloat(monthEntry.debts);
};

const lastMonth = computed(() => {
    return getEntryBalance(props.data?.at?.(-2) ?? { debts: 0, assets: 0})
});

const thisMonth = computed(() => {
    return getEntryBalance(props.data?.at?.(-1) ?? { debts: 0, assets: 0})
});
const monthMovement = computed(() => {
    return  parseFloat(thisMonth.value) - parseFloat(lastMonth.value);
});

const monthMovementVariance = computed(() => {
    return  (monthMovement.value /  parseFloat(lastMonth.value) * 100.00).toFixed(2);
});
</script>

<template>
  <AppLayout :title="metaData.title">
    <template #header>
      <TrendSectionNav :sections="trends" />
    </template>

    <TrendTemplate title="Finance" ref="financeTemplateRef" :hide-panel="true">
        <WidgetTitleCard
            :title="metaData.title"
            class="mt-5"
        >
            <template #title>
                <div>
                    <h4>
                        {{ formatMoney(thisMonth)}}
                    </h4>
                    <p class="space-x-1">
                        <span class="text-success text-sms bg-success/10 px-2 rounded-md">
                            {{ monthMovementVariance }}%
                        </span>
                        <span class="text-success text-sm">
                           {{ formatMoney(monthMovement) }}
                       </span>
                       <span class="text-xs">
                           Past Month
                       </span>
                    </p>
                </div>
            </template>


        <section class="relative flex flex-wrap items-center justify-center w-full bg-base-lvl-3 md:flex-nowrap md:space-x-8">
            <ChartNetWorth
                hide-headers
                style="background: white; width: 100%"
                :type="section"
                :series="data"
                :data="data"
                @selected="handleSelection"
                v-bind="metaData.props"
                :title="metaData.title"
                label="name"
                value="total"
                type="bar"
                :legend="false"
                data-item-total="total_amount"
            />
        </section>
        <template #afterActions>
            <div class="flex justify-end items-center">
                <ElDatePicker
                    class="w-full h-12 border-none bg-base-lvl-1 text-body"
                    v-model="pageState.dates.startDate"
                    v-model:endDate="pageState.dates.endDate"
                    @change="executeSearchWithDelay(500)"
                    controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
                    next-mode="3M"
                />
                <ElDatePicker
                    class="w-full h-12 border-none bg-base-lvl-1 text-body"
                    v-model="pageState.dates.endDate"
                    @change="executeSearchWithDelay(500)"
                    controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
                    next-mode="3M"
                />
                <RangeFilters
                    class="w-fit"
                    v-model:startDate="pageState.dates.startDate"
                    v-model:endDate="pageState.dates.endDate"
                    defaultRange="3M"
                    method="back"
                    :ranges="[
                        { label: '3M', value: [90, 0], tooltip: '3 Months' },
                        { label: '6M', value: [180, 0], tooltip: '6 Months' },
                        { label: '1Y', value: [365, 0], tooltip: '1 Year' },
                    ]"
                />
            </div>
        </template>
      </WidgetTitleCard>
    </TrendTemplate>
  </AppLayout>
</template>

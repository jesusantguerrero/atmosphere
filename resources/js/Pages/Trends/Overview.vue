<script setup lang="ts">
import { computed, toRefs } from "vue";
import { router } from "@inertiajs/vue3";
// @ts-ignore
import { AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import ChartNetWorth from "@/Components/ChartNetworth.vue";
import IncomeExpenses from "@/Components/IncomeExpenses.vue";

import { trendOptions } from "./Partials/trendOptions";
import TrendTemplate from "./Partials/TrendTemplate.vue";
import TrendSectionNav from "./Partials/TrendSectionNav.vue";
import ChartComparison from "@/Components/widgets/ChartComparison.vue";
import WidgetTitleCard from "@/Components/molecules/WidgetTitleCard.vue";

import ExpenseChartWidget from "@/domains/transactions/components/ExpenseChartWidget.vue";

import { useServerSearch } from "@/composables/useServerSearch";
import AccountFilters from "@/domains/transactions/components/AccountFilters.vue";
import WidgetYearSpending from "@/Components/widgets/WidgetYearSpending.vue";

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



const components = {
    groups: ExpenseChartWidget,
    categories: ExpenseChartWidget,
    netWorth: ChartNetWorth,
    incomeExpenses: IncomeExpenses,
    incomeExpensesGraph:  ChartNetWorth,
    spendingYear: WidgetYearSpending,
    assignedYear: WidgetYearSpending,
}

const trendComponent = computed(() => {
    return components[props.metaData.name] || ExpenseChartWidget
})

const isCategoryTrend = computed(() => {
    return ['group', 'categories', 'payee'].includes(props.metaData.name)
})


const isYearReport = computed(() => {
    return ['spendingYear', 'assignedYear'].includes(props.metaData.name)
})

const cashflowEntities = {
    groups: {
        label: 'Groups',
        value: '/trends/groups'
    },
    categories: {
        label: 'Categories',
        value: '/trends/categories'
    },
    payees: {
        label: 'Payees',
        value: '/trends/payees'
    }
}
const isFilterSelected = (filterValue: string) => {
    const currentStatus = location.pathname;
    return currentStatus.includes(filterValue);
}
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
                    next-mode="month"
                />

        </template>
      </TrendSectionNav>
    </template>

    <TrendTemplate title="Finance" ref="financeTemplateRef" :hide-panel="!isCategoryTrend">
        <component
            class="mt-5"
            v-if="isYearReport"
            :is="trendComponent"
            style="background: white; width: 100%"
            :type="section"
            :series="data"
            :data="data"
            :cols="1"
            @selected="handleSelection"
            v-bind="metaData.props"
            :title="metaData.title"
            label="name"
            value="total"
            :legend="false"
            data-item-total="total_amount"
        />
        <WidgetTitleCard
            v-else
            :title="metaData.title"
            class="mt-5"
        >
            <section class="relative flex flex-wrap items-center justify-center w-full bg-base-lvl-3 md:flex-nowrap md:space-x-8">
                <component
                    :is="trendComponent"
                    style="background: white; width: 100%"
                    :type="section"
                    :series="data"
                    :data="data"
                    :cols="1"
                    @selected="handleSelection"
                    v-bind="metaData.props"
                    :title="metaData.title"
                    label="name"
                    value="total"
                    :legend="false"
                    data-item-total="total_amount"
                />
            </section>
            <template #afterActions v-if="isCategoryTrend">
                <div class="flex overflow-hidden text-white border rounded-md bg-primary border-primary min-w-max">
                    <button
                        v-for="(item, statusName) in cashflowEntities"
                        class="px-2 py-1.5 flex items-center border border-transparent hover:bg-accent"
                        :class="{'bg-white text-primary border-primary hover:text-white': isFilterSelected(statusName)}"
                        :key="statusName"
                        @click="router.visit(item.value)">
                        {{ item.label }}
                    </button>
                </div>
            </template>
      </WidgetTitleCard>
      <template #panel>
        <section class="mt-5 mr-4 px-5 pt-2 pb-4 space-y-4 text-left border-b rounded-md shadow-xl bg-base-lvl-3">
            <h4 class="font-bold"> Filters </h4>
            <AccountFilters
                class="w-full bg-red-500"
                include-labels
                col
                :tag-max-count="1"
                v-model:accounts="pageState.filters.account"
                v-model:categories="pageState.filters.category"
            />
        </section>
      </template>
    </TrendTemplate>
  </AppLayout>
</template>

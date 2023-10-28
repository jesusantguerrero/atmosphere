<script setup lang="ts">
import { computed, toRefs } from "vue";
import { Link, router } from "@inertiajs/vue3";
// @ts-ignore
import { AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import ChartNetWorth from "@/Components/ChartNetworth.vue";
import IncomeExpenses from "@/Components/IncomeExpenses.vue";

import TrendTemplate from "./Partials/TrendTemplate.vue";
import TrendSectionNav from "./Partials/TrendSectionNav.vue";
import ChartComparison from "@/Components/widgets/ChartComparison.vue";
import WidgetTitleCard from "@/Components/molecules/WidgetTitleCard.vue";
import Collapse from "@/Components/molecules/Collapse.vue";

import YearSummary from "@/domains/transactions/components/YearSummary.vue";
import ExpenseChartWidget from "@/domains/transactions/components/ExpenseChartWidget.vue";

import { useServerSearch } from "@/composables/useServerSearch";

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

const components = {
    groups: ExpenseChartWidget,
    categories: ExpenseChartWidget,
    netWorth: ChartNetWorth,
    incomeExpenses: IncomeExpenses,
    incomeExpensesGraph:  ChartNetWorth,
    spendingYear: ChartComparison
}

const trendComponent = computed(() => {
    return components[props.metaData.name] || ExpenseChartWidget
})

const isCategoryTrend = computed(() => {
    return ['group', 'categories', 'payees'].includes(props.metaData.name)
})


const isYearSpending = computed(() => {
    return ['spendingYear'].includes(props.metaData.name)
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
const isFilterSelected = (filterValue) => {
    const currentStatus = location.pathname;
    return currentStatus.includes(filterValue);
}
</script>

<template>
  <AppLayout :title="metaData.title">
    <template #header>
      <TrendSectionNav :sections="trends">
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

    <TrendTemplate title="Finance" ref="financeTemplateRef">

        <component
            class="mt-5"
            v-if="isYearSpending"
            :is="trendComponent"
            style="background: white; width: 100%"
            :type="section"
            :series="data"
            :data="data"
            @selected="handleSelection"
            v-bind="metaData.props"
            :title="metaData.title"
            label="name"
            value="total"
            :legend="false"
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
                @selected="handleSelection"
                v-bind="metaData.props"
                :title="metaData.title"
                label="name"
                value="total"
                :legend="false"
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
            <Collapse
                title="Reports"
                title-class="p-2 mt-6 font-bold rounded-md bg-base-lvl-3 text-body-1 bg-base-lvl-1 "
                content-class="pb-4 bg-base-lvl-3"
            >
                <template #content>
                    <div class="divide-y divide-base-lvl-2 bg-base-lvl-3">
                        <Link
                            :href="trend.url"
                            v-for="trend in trends"
                            :key="trend.label"
                            class="block px-2 py-4 cursor-pointer hover:bg-base-lvl-2"
                        >
                            {{ trend.label }}
                        </Link>
                    </div>
                </template>
            </Collapse>
      </template>
    </TrendTemplate>
  </AppLayout>
</template>

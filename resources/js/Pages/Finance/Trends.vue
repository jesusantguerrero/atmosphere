<template>
  <AppLayout :title="metaData.title">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
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
            <AtDatePager
                class="w-full h-12 border-none bg-base-lvl-1 text-body"
                v-model:startDate="pageState.dates.startDate"
                v-model:endDate="pageState.dates.endDate"
                @change="executeSearchWithDelay()"
                controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
                next-mode="month"
            />
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate title="Finance" ref="financeTemplateRef">
      <section>
        <div class="flex flex-wrap items-center justify-center bg-base-lvl-3 mt-5 md:flex-nowrap md:space-x-8">
          <div class="w-full relative">
            <component
                :is="trendComponent"
                style="background: white; width: 100%"
                :series="data"
                :data="data"
                @clicked="handleSelection"
                v-bind="metaData.props"
                :title="metaData.title"
                label="name"
                value="total"
                :legend="false"
            />
          </div>
        </div>
      </section>

      <template #panel>
            <div class="divide-y-2">
                <Link :href="trend.link" v-for="trend in trends" :key="trend.name" class="block px-2 py-4 cursor-pointer hover:bg-base-lvl-3">
                    {{ trend.name }}
                </Link>
            </div>
      </template>
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup>
import { computed, toRefs } from "vue";
import { AtDatePager } from "atmosphere-ui";
import { Link, router } from "@inertiajs/vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import ChartNetWorth from "@/Components/ChartNetworth.vue";
import IncomeExpenses from "@/Components/IncomeExpenses.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import ExpenseChartWidget from "@/Components/widgets/ExpenseChartWidget.vue";

import { useServerSearch } from "@/composables/useServerSearch";
import ChartComparison from "@/Components/widgets/ChartComparison.vue";

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
});

const { serverSearchOptions } = toRefs(props);
const {state: pageState, executeSearchWithDelay } = useServerSearch(serverSearchOptions, {
    manual: true
});

const handleSelection = (index) => {
    const parent = props.data[index]
    if (!props.metaData.parent_name) {
        router.visit(`/trends/categories?filter[parent_id]=${parent.id}`)
    }
}

const trends = [
    {
        name: 'Cashflow',
        link: '/trends'
    },
    {
        name: 'Category Trends',
        link: '/trends/categories'
    },
    {
        name: 'Net Worth',
        link: '/trends/net-worth'
    },
    {
        name: 'Income v Expenses',
        link: '/trends/income-expenses'
    },
    {
        name: 'Income vs Expenses Graph',
        link: '/trends/income-expenses-graph'
    }
]


const components = {
    groups: ExpenseChartWidget,
    categories: ExpenseChartWidget,
    netWorth: ChartNetWorth,
    incomeExpenses: IncomeExpenses,
    incomeExpensesGraph:  ChartComparison
}

const trendComponent = computed(() => {
    return components[props.metaData.name] || ExpenseChartWidget
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

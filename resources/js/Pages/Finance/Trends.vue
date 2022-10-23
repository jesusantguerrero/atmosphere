<template>
  <AppLayout :title="metaData.title">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
            <AtDatePager
                class="w-full h-12 border-none bg-base-lvl-1 text-body"
                v-model:startDate="pageState.dates.startDate"
                v-model:endDate="pageState.dates.endDate"
                controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
                next-mode="month"
            />
          <!-- <div>
            <AtButton class="rounded-md text-primary w-32" @click="onPrint">
                Print
            </AtButton>
          </div> -->
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate title="Finance" :accounts="accounts" ref="financeTemplateRef">
      <section>
        <div class="flex flex-wrap mt-5 md:flex-nowrap md:space-x-8">
          <div class="w-full md:w-full">
            <component :is="trendComponent"
                style="background: white; width: 100%"
                :series="data"
                :data="data"
                @clicked="handleSelection"
                label="name"
                value="total"
                :legend="false"
            />
          </div>
        </div>
      </section>

      <template #panel>
            <div class="divide-y-2">
                <Link :href="trend.link" v-for="trend in trends" :key="trend.name" class="block py-4 px-2 hover:bg-base-lvl-3 cursor-pointer">
                    {{ trend.name }}
                </Link>
            </div>
      </template>
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup>
import { computed, toRefs } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { AtButton, AtDatePager } from "atmosphere-ui";
import { Link } from "@inertiajs/inertia-vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import DonutChart from "@/Components/organisms/DonutChart.vue";
import ChartNetWorth from "@/Components/ChartNetworth.vue";
import IncomeExpenses from "@/Components/IncomeExpenses.vue";
import SectionCard from "@/Components/molecules/SectionCard.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";

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
});

const { serverSearchOptions } = toRefs(props);
const {state: pageState, executeSearch }= useServerSearch(serverSearchOptions);

const handleSelection = (index) => {
    const parent = props.data[index]
    if (!props.metaData.parent_name) {
        Inertia.visit(`/trends/categories?filter[parent_id]=${parent.id}`)
    }
}

const trends = [
    {
        name: 'Category Group Trends',
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
    }
]

const components = {
    groups: DonutChart,
    categories: DonutChart,
    netWorth: ChartNetWorth,
    incomeExpenses: IncomeExpenses
}

const trendComponent = computed(() => {
    return components[props.metaData.name] || DonutChart
})

const onPrint = () => {
    print();
}
</script>

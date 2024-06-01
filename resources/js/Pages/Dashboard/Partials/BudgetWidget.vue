<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from "vue-i18n";
import VueApexCharts from "vue3-apexcharts";
import { router } from "@inertiajs/vue3"

import WidgetTitleCard from '@/Components/molecules/WidgetTitleCard.vue';
import BudgetProgress from '@/domains/budget/components/BudgetProgress.vue';

import { formatMoney } from '@/utils';
import { IBudgetStat } from '@/domains/budget/models/budget';

interface Stat {
    label: string;
    value: string|number;
}

const props = defineProps<{
    budget: IBudgetStat
}>();

const stats = ref<{
    budgeted: Stat;
    spending: Stat;
    budgetedSpending: Stat;
}>({
    budgeted: {
        value: props.budget.spending,
        label: 'Budgeted'
    },
    spending: {
        value: props.budget.savings,
        label: 'Savings'
    },
    budgetedSpending: {
        value: 0,
        label: 'Budgeted spending'
    },
});

const labels: Record<string, string> = {
  budgeted: "Expired",
  spending: "Expiring this month",
  budgetedSpending: "Expire within 3 months",
};

const { t } = useI18n();
const chartRef = ref();
const chartConfig = {
  options: {
    chart: {
      id: "vuechart-example",
      type: "donut",
      offsetY: -20,
      sparkline: {
        enabled: true,
      },
    },
    fill: {
      colors: ['#FFFFFF', '#E91E63', '#CF3C68']
    },
    stroke: {
      curve: "smooth",
    },
    dropShadow: {
      enabled: true,
      top: 3,
      left: 0,
      blur: 1,
      opacity: 0.5,
    },
    plotOptions: {
      pie: {
        startAngle: -90,
        endAngle: 90,
        offsetY: 10,
      },
    },
    grid: {
      padding: {
        bottom: -80,
      },
    },
    labels: Object.values(stats.value).map((item) => t(item.label)),
  },
  series: Object.values(stats.value).map(item => item.value),
};
</script>

<template>
    <WidgetTitleCard
        title="Budget balance"
        class="hidden md:block bg-primary text-white"
        :hide-divider="true"
        :action="{
            label: 'Budget',
            iconClass: 'fa fa-chevron-right text-white'
        }"
        @action="router.visit('/budgets')"
    >
        <section class="w-full">
            <section class="w-full  py-3 relative h-[155px]">
                <article style="width: 100%; height: 300px" class="relative py-1 mb-10">
                  <VueApexCharts
                    ref="chartRef"
                    width="100%"
                    height="100%"
                    type="donut"
                    :options="chartConfig.options"
                    :series="chartConfig.series.map((value) => Number(value))"
                  />
                </article>
              </section>
              <header class="mt-4 border-t py-4 flex items-start justify-between pb-2">
                    <h1 class="font-bold flex items-center w-full">
                        <section class="bg-white text-primary w-8 h-8 rounded-full flex items-center justify-center mr-2"
                        >
                            <IMdiBankTransfer />
                        </section>
                        Total
                    </h1>
                    <section class="space-x-2 w-full">
                        <h2 class="text-lg flex items-center  font-bold">
                            <span class="bg-white rounded-md text-error text-xs px-1 py-0.5 mr-1">
                                12%
                            </span>
                            <span>
                                {{ formatMoney(stats.budgeted.value) }}
                            </span>
                        </h2>
                    </section>
              </header>
              <article class="space-y-2 mt-4">
                <BudgetProgress
                    class="h-2 rounded-sm"
                    :goal="budget.total"
                    :current="budget.spending"
                    :progress-class="['bg-white', 'bg-primaryDark/60']"
                    :show-labels="false"
                >
                    <template v-slot:before="{ progress }">
                        <header class="mb-1 font-bold text-xs flex justify-between w-full ">
                        <section>
                            For spending
                        </section>
                        <section >
                            {{ formatMoney(budget.spending) }} / {{ formatMoney(budget?.total) }}
                            ({{ progress }}%)
                        </section>
                        </header>
                    </template>
                </BudgetProgress>
                <BudgetProgress
                    class="h-2 rounded-sm"
                    :goal="budget.total"
                    :current="budget.savings"
                    :progress-class="['bg-white', 'bg-primaryDark/60']"
                    :show-labels="false"
                >
                <template v-slot:before="{ progress }">
                    <header class="mb-1 font-bold text-xs flex justify-between w-full ">
                    <section>
                        For savings
                    </section>
                    <section >
                        {{ formatMoney(budget.savings) }} / {{ formatMoney(budget?.total) }}
                        ({{ progress }}%)
                    </section>
                    </header>
                </template>
                </BudgetProgress>
              </article>
        </section>

        <template #icon>
            <IMdiCheck />
        </template>
    </WidgetTitleCard>
</template>


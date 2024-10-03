<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3"
import VueApexCharts from "vue3-apexcharts";
import { ElTooltip } from 'element-plus';

import WidgetTitleCard from '@/Components/molecules/WidgetTitleCard.vue';
import BudgetProgress from '@/domains/budget/components/BudgetProgress.vue';
import PointAlert from "@/Components/atoms/PointAlert.vue";

import { formatMoney } from '@/utils';
import { IBudgetStat } from '@/domains/budget/models/budget';
import { getVariances } from '@/domains/transactions';
import axios from 'axios';

interface Stat {
    label: string;
    value: string|number;
}

const props = defineProps<{
    budgets: IBudgetStat[]
}>();

const stats = ref<{
    budgeted: Stat;
    spending: Stat;
    budgetedSpending: Stat;
}>({
    budgeted: {
        value: props.budgets?.at(-1)?.spending,
        label: 'Budgeted'
    },
    spending: {
        value: props.budgets?.at(-1)?.savings,
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

interface MonthBudget {
    forSpending: number
    forSavings: number
    total: number
}

const currentBudget = computed<MonthBudget>(() => {
    return {
        forSpending: props.budgets?.at(-1)?.spending,
        forSavings: props.budgets?.at(-1)?.savings,
        total: props.budgets?.at(-1)?.total,
    }
});

const prevBudget = computed<MonthBudget>(() => {
    return {
        forSpending: props.budgets?.at(0)?.spending,
        forSavings: props.budgets?.at(0)?.savings,
        total: props.budgets?.at(0)?.total,
    }
});

const variance = computed(() => {
    return getVariances(currentBudget.value.total, prevBudget.value.total)
})

const fetchBudgetAlerts = async () => {
    return axios.get(`/budget-alerts`).then<Record<string, string>>(({ data }) => {
        return data;
    })
}
const alerts = ref<Record<string, string>>({})
const hasAlerts = computed(() => Object.values(alerts.value).length)
fetchBudgetAlerts().then(data => {
    alerts.value = data;
});

</script>

<template>
    <WidgetTitleCard
        title="Budget balance"
        class="hidden md:block bg-primary text-white overflow-hidden"
        :hide-divider="true"
        :action="{
            label: 'Budget',
            iconClass: 'fa fa-chevron-right text-white'
        }"
        @action="router.visit('/budgets')"
    >
        <section class="w-full">
            <section class="w-full  pb-3 relative h-[80px]">
                <article style="width: 100%; height: 190px" class="relative pb-1 mb-10">
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
                    <h1 class="font-bold flex items-center ">
                        <section class="bg-white text-primary w-8 h-8 rounded-full flex items-center justify-center mr-2"
                        >
                            <IMdiBankTransfer />
                        </section>
                        Total
                    </h1>
                    <section class="space-x-2 ">
                        <h2 class="flex items-center font-bold">
                            <ElTooltip :content="formatMoney(prevBudget.total)">
                                <span class="bg-white inline-block cursor-pointer rounded-md text-error text-xs px-1 py-0.5 mr-1" >
                                    {{variance}} %
                                </span>
                            </ElTooltip>
                            <span >
                                {{ formatMoney(stats.budgeted.value) }}
                            </span>
                        </h2>
                    </section>
              </header>
              <article class="space-y-2 mt-2">
                <BudgetProgress
                    class="h-2 rounded-sm"
                    :goal="currentBudget.total"
                    :current="currentBudget.forSpending"
                    :progress-class="['bg-white', 'bg-primaryDark/60']"
                    :show-labels="false"
                >
                    <template v-slot:before="{ progress }">
                        <header class="mb-1 font-bold text-xs flex justify-between w-full ">
                        <section>
                            For spending
                        </section>
                        <section >
                            {{ formatMoney(currentBudget.forSpending) }} 
                            ({{ progress }}%)
                        </section>
                        </header>
                    </template>
                </BudgetProgress>
                <BudgetProgress
                    class="h-2 rounded-sm"
                    :goal="currentBudget.total"
                    :current="currentBudget.forSavings"
                    :progress-class="['bg-white', 'bg-primaryDark/60']"
                    :show-labels="false"
                >
                <template v-slot:before="{ progress }">
                    <header class="mb-1 font-bold text-xs flex justify-between w-full ">
                    <section>
                        For savings
                    </section>
                    <section >
                        {{ formatMoney(currentBudget.forSavings) }} 
                        ({{ progress }}%)
                    </section>
                    </header>
                </template>
                </BudgetProgress>
              </article>
        </section>

        <template #icon>
            <ElTooltip effect="dark" :content="`Your budget has ${hasAlerts} overspent categories`" placement="top" v-if="hasAlerts">
            <div class="cursor-pointer relative"  @click="router.visit('/budgets?custom[mode]=overspent')">
                <IMdiBell />
                <PointAlert />
            </div>
            </ElTooltip>
            <IMdiCheck v-else />
        </template>
    </WidgetTitleCard>
</template>


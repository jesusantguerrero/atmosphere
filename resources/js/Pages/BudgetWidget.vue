<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { ITransaction } from '@/domains/transactions/models';
import axios from 'axios';
import WidgetTitleCard from '@/Components/molecules/WidgetTitleCard.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import { useTransactionStore } from '@/store/transactions';
import { useI18n } from "vue-i18n";
import VueApexCharts from "vue3-apexcharts";
import { formatMoney } from '@/utils';
import BudgetProgress from '@/domains/budget/components/BudgetProgress.vue';

interface Stat {
    label: string;
    value: string|number;
}
const stats = ref<{
    budgeted: Stat;
    spending: Stat;
    budgetedSpending: Stat;
}>({
    budgeted: {
        value: 2000,
        label: 'Budgeted'
    },
    spending: {
        value: 1000,
        label: 'spending'
    },
    budgetedSpending: {
        value: 1500,
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
const legend = computed(() => {
return chartConfig.options.labels.map((label, index) => {
    return {
    label: label,
    value: chartConfig.series[index],
    color: "",
    };
});
});

const transactionsDraft = ref([]);
const isLoadingDrafts = ref(false);
const fetchTransactions = async () => {
    const url = `/api/finance/transactions?filter[status]=draft&limit=10`;
    return axios.get(url).then<ITransaction[]>(({ data }) => {
        transactionsDraft.value = data;
        isLoadingDrafts.value = false
    })
}

onMounted(() => {
    fetchTransactions()
})

const isLoading = ref(false);
const updateTransactions = () => {
    isLoading.value = true;
    fetchTransactions().finally(() => {
        isLoading.value = false;
    })
}

const transactionStore = useTransactionStore();
const unsubscribe =  transactionStore.$onAction(({
    name,
    store,
    args,
    after
}) => {
    after((result) => {
        const [savedValue, action, originalData] = args;
        if (originalData && originalData.status == 'draft' && savedValue.status == 'verified') {
            fetchTransactions();
        }
    })
})
</script>

<template>
    <WidgetTitleCard title="Budget balance" class="hidden md:block bg-primary text-white" :hide-divider="true">
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
                  <!-- <IClarityContractLine class="absolute text-4xl report-icon-center" /> -->
                  <!-- <section class="flex absolute w-full bottom-28">
                    <article v-for="item in legend" class="w-full text-center">
                      <header>
                        <span class="font-bold"> {{ item.value }}</span>
                      </header>
                      <p>{{ item.label }}</p>
                    </article>
                  </section> -->
                </article>
              </section>
              <header class="mt-4 border-t py-4 flex items-start justify-between pb-2">
                    <h1 class="font-bold flex items-center w-full">
                        <section class="bg-white text-primary w-8 h-8 rounded-full flex items-center justify-center mr-2"
                        >
                            <IMdiBankTransfer />
                        </section>
                        Budget total
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
                        <p class="text-xs">
                            compared to {{  formatMoney(25000) }} last month
                        </p>
                    </section>
              </header>
              <article class="space-y-2 mt-4">
                <BudgetProgress
                    class="h-2 rounded-sm"
                    :goal="2000"
                    :current="1500"
                    :progress-class="['bg-white', 'bg-primaryDark/60']"
                    :show-labels="false"
                >
                    <template v-slot:before="{ progress }">
                        <header class="mb-1 font-bold text-xs flex justify-between w-full ">
                        <section>
                            Class a
                        </section>
                        <section >
                            {{ formatMoney(totals?.paid) }} / {{ formatMoney(totals?.total) }}
                            ({{ progress }}%)
                        </section>
                        </header>
                    </template>
                </BudgetProgress>
                <BudgetProgress
                    class="h-2 rounded-sm"
                    :goal="1200"
                    :current="700"
                    :progress-class="['bg-white', 'bg-primaryDark/60']"
                    :show-labels="false"
                />
              </article>
        </section>

        <template #icon>
            <IMdiCheck />
        </template>

        <template #action>
            <LogerButton class="primary border-primaryDark/60 bg-primaryDark/60 rounded-full text-sm" @click="updateTransactions()" :disabled="isLoading">
                This month
            </LogerButton>
        </template>
    </WidgetTitleCard>
</template>


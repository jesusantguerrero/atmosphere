<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { ITransaction } from "@/domains/transactions/models";
import axios from "axios";
import WidgetTitleCard from "@/Components/molecules/WidgetTitleCard.vue";
import { useTransactionStore } from "@/store/transactions";
import { useI18n } from "vue-i18n";
import { formatMoney } from "@/utils";
import BudgetProgress from "@/domains/budget/components/BudgetProgress.vue";

interface Stat {
  label: string;
  value: string | number;
}
const stats = ref<{
  budgeted: Stat;
  spending: Stat;
  budgetedSpending: Stat;
}>({
  budgeted: {
    value: 2000,
    label: "Budgeted",
  },
  spending: {
    value: 1000,
    label: "spending",
  },
  budgetedSpending: {
    value: 1500,
    label: "Budgeted spending",
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
      colors: ["#FFFFFF", "#E91E63", "#CF3C68"],
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
  series: Object.values(stats.value).map((item) => item.value),
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
    isLoadingDrafts.value = false;
    return data;
  });
};

onMounted(() => {
  fetchTransactions();
});

</script>

<template>
  <WidgetTitleCard
    title="Net-Worth Balance"
    class="hidden md:block base-lvl-3 text-body-1"
    :hide-divider="true"
  >
    <template #icon>
      <IMdiCheck />
    </template>
    <section class="w-full">
      <WidgetHeaderRow title="Net-worth" :value="20000">
        <template #icon>
          <IMdiBankTransfer />
        </template>
      </WidgetHeaderRow>
      <article class="mt-4 space-y-2">
        <BudgetProgress
          class="h-2 rounded-sm"
          :goal="2000"
          :current="1500"
          :progress-class="['bg-white', 'bg-primaryDark/60']"
          :show-labels="false"
        >
          <template v-slot:before="{ progress }">
            <header class="flex justify-between w-full mb-1 text-xs font-bold">
              <section>Class a</section>
              <section>
                {{ formatMoney(totals?.paid) }} / {{ formatMoney(totals?.total) }} ({{
                  progress
                }}%)
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
  </WidgetTitleCard>
</template>

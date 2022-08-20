<template>
  <AppLayout>
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <AtDatePager
            class="w-full h-12 border-none bg-base-lvl-1 text-body"
            v-model="pageState.date"
            v-model:dateSpan="pageState.dateSpan"
            v-model:startDate="pageState.searchOptions.date.startDate"
            v-model:endDate="pageState.searchOptions.date.endDate"
            controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
            next-mode="month"
          />
          <div>
            <AtButton class="rounded-md text-primary w-32"
              >Print</AtButton
            >
          </div>
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate title="Finance" :accounts="accounts" ref="financeTemplateRef">
      <section>
        <div class="flex flex-wrap mt-5 md:flex-nowrap md:space-x-8">
          <div class="w-full md:w-full">
            <SectionCard
              section-title="Expenses by category"
            >
              <DonutChart
                style="background: white; width: 100%"
                :series="data"
                label="name"
                value="total"
                :legend="false"
            />
            </SectionCard>
          </div>
        </div>
      </section>

      <template #panel>
            <div>Hello I am a pannel </div>
      </template>
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup>
import { computed, ref, toRefs } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { AtButton, AtDatePager } from "atmosphere-ui";
import AppLayout from "@/Layouts/AppLayout.vue";
import FinanceCard from "@/Components/molecules/FinanceCard.vue";
import FinanceVarianceCard from "@/Components/molecules/FinanceVarianceCard.vue";
import TransactionsTable from "@/Components/organisms/TransactionsTable.vue";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import {
  useTransactionModal,
  transactionDBToTransaction,
  categoryDBToTransaction,
} from "@/domains/transactions";
import BudgetProgress from "@/Components/molecules/BudgetProgress.vue";
import DonutChart from "@/Components/organisms/DonutChart.vue";
import SectionCard from "@/Components/molecules/SectionCard.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import { useSelect } from "@/utils/useSelects";
import formatMoney from "@/utils/formatMoney";
import { useServerSearch } from "./useServerSearch";

const { serverSearchOptions } = toRefs(props);
const pageState = useServerSearch(serverSearchOptions);

const financeTemplateRef = ref(null);
const { openModalFor, handleEdit } = useTransactionModal(financeTemplateRef);
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
  serverSearchOptions: {
    type: Object,
    default: () => ({}),
  },
});

const { categoryOptions: transformCategoryOptions } = useSelect();
transformCategoryOptions(props.categories, "accounts", "categoryOptions");
transformCategoryOptions(props.accounts, "accounts", "accountsOptions");
const getVariances = (current, last) => {
  if (last === 0) {
    return 0;
  }
  const variance = ((current - last) / last) * 100;
  return variance.toFixed(2);
};

const incomeVariance = computed(() => {
  return getVariances(props.income, props.lastMonthIncome);
});

const expenseVariance = computed(() => {
  return getVariances(props.transactionTotal, props.lastMonthExpenses) || 0;
});

const topCategories = props.expensesByCategory.slice(0, 4);
</script>

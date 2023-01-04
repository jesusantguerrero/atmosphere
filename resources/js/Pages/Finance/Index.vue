<template>
  <AppLayout>
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
          <div>
            <LogerButton  variant="inverse">
                Import Transactions
            </LogerButton>
          </div>
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate title="Finance" :accounts="accounts" ref="financeTemplateRef">
      <section class="grid grid-cols-2 gap-12 mt-4">
            <WidgetTitleCard title="Summary" class="w-full">
                <div
                    class="flex justify-between lg:space-x-4 overflow-hidden lg:flex-nowrap flex-wrap w-full"
                >
                    <div class="w-full mx-auto space-y-2">
                        <FinanceCard
                            class="text-body-1 bg-base-lvl-1"
                            title="Income"
                            :value="formatMoney(income)"
                            :subtitle="`${lastMonthName}: ${incomeVariance}%`"
                        />
                        <FinanceCard
                            class="text-body-1 bg-base-lvl-1"
                            title="Savings"
                            :value="formatMoney(savings)"
                            :subtitle="`Total: ${formatMoney(savings)}`"
                        />
                        <BudgetProgress
                            :goal="budgetTotal"
                            :current="transactionTotal"
                            class="border-t h-8 rounded-b-lg text-white"
                        />
                    </div>
                    <FinanceVarianceCard
                        class="w-full"
                        title="Expenses"
                        :variance-title="lastMonthName"
                        :value="formatMoney(transactionTotal)"
                        :variance="expenseVariance"
                        :variance-amount="formatMoney(lastMonthExpenses)"
                        @click="$inertia.visit('/finance/transactions')"
                    />
                </div>
            </WidgetTitleCard>

            <WidgetTitleCard title="Planned Transactions" class="hidden md:block">
                <TransactionsTable
                  class="w-full"
                  table-class="overflow-auto text-sm rounded-t-lg shadow-md bg-base-lvl-3 p-2 w-full"
                  :transactions="planned"
                  :parser="plannedDBToTransaction"
                  @edit="handleEdit"
                />

                <template #action>
                    <AtButton
                      class="flex items-center text-primary"
                      @click="Inertia.visit('/transactions?filter[status]=planned')"
                    >
                      <span> See scheduled</span>
                      <i class="ml-2 fa fa-chevron-right"></i>
                    </AtButton>
                </template>
            </WidgetTitleCard>

            <CategoryTrendsPreview
                class="w-full"
                :category-data="topCategories"
                :group-data="expensesByCategoryGroup"
            />

            <WidgetTitleCard title="Transactions" class="w-full">
                <TransactionsTable
                    class="w-full"
                    table-class="overflow-auto text-sm"
                    :transactions="transactions"
                    :parser="transactionDBToTransaction"
                    @edit="''"
                />

                <template #action>
                    <at-button class="text-primary" @click="''"
                    ><i class="fa fa-plus"></i> Add transaction</at-button
                    >
                </template>
            </WidgetTitleCard>
      </section>
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup>
import { computed, ref, toRefs } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { AtButton, AtDatePager } from "atmosphere-ui";
import AppLayout from "@/Components/templates/AppLayout.vue";
import FinanceCard from "@/Components/molecules/FinanceCard.vue";
import FinanceVarianceCard from "@/Components/molecules/FinanceVarianceCard.vue";
import TransactionsTable from "@/Components/organisms/TransactionsTable.vue";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import {
  useTransactionModal,
  transactionDBToTransaction,
  plannedDBToTransaction,
  categoryDBToTransaction,
getVariances,
} from "@/domains/transactions";
import BudgetProgress from "@/Components/molecules/BudgetProgress.vue";
import DonutChart from "@/Components/organisms/DonutChart.vue";
import SectionCard from "@/Components/molecules/SectionCard.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import { useSelect } from "@/utils/useSelects";
import formatMoney from "@/utils/formatMoney";
import { useServerSearch } from "@/composables/useServerSearch";
import CategoryTrendsPreview from "@/Components/Modules/finance/CategoryTrendsPreview.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import WidgetTitleCard from "@/Components/molecules/WidgetTitleCard.vue";
import { format, subMonths } from "date-fns";

const { serverSearchOptions } = toRefs(props);
const {state: pageState} = useServerSearch(serverSearchOptions);

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  planned: {
    type: Array,
    default() {
      return [];
    },
  },
  expensesByCategory: {
    type: Array,
    default() {
      return [];
    },
  },
  expensesByCategoryGroup: {
    type: Array,
    default() {
      return [];
    },
  },
  budgetTotal: {
    type: Number,
    default: 0,
  },
  income: {
    type: Number,
    default: 0,
  },
  savings: {
    type: Number,
  },
  lastMonthIncome: {
    type: Number,
    default: 0,
  },
  transactionTotal: {
    type: Number,
    default: 0,
  },
  lastMonthExpenses: {
    type: Number,
    default: 0,
  },
  transactions: {
    type: Array,
    default() {
      return [];
    },
  },
  categories: {
    type: Array,
    default() {
      return [];
    },
  },
  accounts: {
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

const lastMonthName = computed(() => {
    try {
        return format(subMonths(pageState.dates.startDate, 1), 'MMM');
    } catch (e) {
        return 'LM'
    }
})
const incomeVariance = computed(() => {
  return getVariances(props.income, props.lastMonthIncome);
});

const expenseVariance = computed(() => {
  return getVariances(props.transactionTotal, props.lastMonthExpenses) || 0;
});

const topCategories = props.expensesByCategory.slice(0, 4);
</script>

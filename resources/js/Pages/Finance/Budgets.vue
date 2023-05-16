<template>
  <AppLayout>
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <AtDatePager
            class="w-full h-12 border-none bg-base-lvl-1 text-body"
            v-model:startDate="pageState.dates.startDate"
            v-model:endDate="pageState.dates.endDate"
            @change="executeSearchWithDelay"
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
      Hola
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup>
import { computed, toRefs } from "vue";
import { router } from "@inertiajs/vue3";
import { AtButton, AtDatePager } from "atmosphere-ui";
import AppLayout from "@/Components/templates/AppLayout.vue";
import FinanceCard from "@/Components/molecules/FinanceCard.vue";
import FinanceVarianceCard from "@/Components/molecules/FinanceVarianceCard.vue";
import TransactionsTable from "@/Components/organisms/TransactionsTable.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import {
    transactionDBToTransaction,
    plannedDBToTransaction,
    getVariances,
    useTransactionModal,
} from "@/domains/transactions";
import BudgetProgress from "@/Components/molecules/BudgetProgress.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import { useSelect } from "@/utils/useSelects";
import formatMoney from "@/utils/formatMoney";
import { useServerSearch } from "@/composables/useServerSearch";
import CategoryTrendsPreview from "@/Components/Modules/finance/CategoryTrendsPreview.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import WidgetTitleCard from "@/Components/molecules/WidgetTitleCard.vue";
import { format, subMonths } from "date-fns";



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
    type: [Number, String],
    default: 0,
  },
  income: {
    type: [Number, String],
    default: 0,
  },
  savings: {
    type: [Number, String],
  },
  lastMonthIncome: {
    type: [Number, String],
    default: 0,
  },
  transactionTotal: {
    type: [Number, String],
    default: 0,
  },
  lastMonthExpenses: {
    type: [Number, String],
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

const { serverSearchOptions } = toRefs(props);
const { state: pageState, executeSearchWithDelay } = useServerSearch(serverSearchOptions, {
    manual: true
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

const { openTransactionModal } = useTransactionModal();
const handleEdit = (transaction) => {
    openTransactionModal({
        transactionData: transaction
    })
}
</script>

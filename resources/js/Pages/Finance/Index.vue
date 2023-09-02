
<script setup lang="ts">
import { computed, toRefs, watch, ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { format, subMonths } from "date-fns";
// @ts-ignore
import { AtButton, AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import WidgetTitleCard from "@/Components/molecules/WidgetTitleCard.vue";

import FinanceCard from "@/Components/molecules/FinanceCard.vue";
import FinanceVarianceCard from "@/Components/molecules/FinanceVarianceCard.vue";
import BudgetProgress from "@/domains/budget/components/BudgetProgress.vue";

import FinanceTemplate from "./Partials/FinanceTemplate.vue";
import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";
import TransactionsList from "@/domains/transactions/components/TransactionsList.vue";
import CategoryTrendsPreview from "@/domains/transactions/components/CategoryTrendsPreview.vue";

import { useServerSearch } from "@/composables/useServerSearch";
import {
    transactionDBToTransaction,
    plannedDBToTransaction,
    getVariances,
    useTransactionModal,
    removeTransaction
} from "@/domains/transactions";
import { useSelect } from "@/utils/useSelects";
import formatMoney from "@/utils/formatMoney";
import { ITransaction } from "@/domains/transactions/models";
import BulkSelectionBar from "@/Components/BulkSelectionBar.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";

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
const handleEdit = (transaction: ITransaction) => {
    openTransactionModal({
        transactionData: transaction
    })
}

const selectedItems = ref([]);
const deleteTransactionsForm = useForm({
    isVisible: false,
    data: [],
})

const deleteBulkTransactions = () => {
    deleteTransactionsForm.transform(() => ({
        data: selectedItems.value,

    })).post(`/finance/transactions/bulk/delete`, {
        onSuccess() {
            deleteTransactionsForm.isVisible = false;
            selectedItems.value = [];
            router.reload({ preserveScroll: true });
        }
    })
};
</script>

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
      <section class="mt-4 space-y-4">
            <WidgetTitleCard title="Summary" class="w-full">
                <div
                    class="flex flex-wrap justify-between w-full overflow-hidden lg:space-x-4 lg:flex-nowrap"
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
                            class="h-8 text-white border-t rounded-b-lg"
                        />
                    </div>
                    <FinanceVarianceCard
                        class="w-full"
                        title="Expenses"
                        :variance-title="lastMonthName"
                        :value="formatMoney(transactionTotal)"
                        :variance="expenseVariance"
                        :variance-amount="formatMoney(lastMonthExpenses)"
                        @click="router.visit('/finance/transactions')"
                    />
                </div>
            </WidgetTitleCard>

            <section class="grid grid-cols-2 gap-2">
                <WidgetTitleCard title="Planned Transactions" class="hidden md:block">
                    <TransactionsList
                      class="w-full"
                      table-class="w-full p-2 overflow-auto text-sm rounded-t-lg shadow-md bg-base-lvl-3"
                      :transactions="planned"
                      v-model:selected="selectedItems"
                      :parser="plannedDBToTransaction"
                      :allow-remove="true"
                      :allow-mark-as-approved="true"
                      :hide-accounts="true"
                      @approved="handleEdit"
                      @removed="removeTransaction($event, ['planned'])"
                    />

                    <template #action>
                        <AtButton
                          class="flex items-center text-primary"
                          @click="router.visit('/transactions?filter[status]=planned')"
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
            </section>

            <WidgetTitleCard title="Transaction history" class="w-full">
                <TransactionsList
                    class="w-full"
                    table-class="overflow-auto text-sm"
                    :transactions="transactions"
                    :parser="transactionDBToTransaction"
                    @edit="''"
                />

                <template #action>
                    <button class="text-primary" @click="''">
                        <i class="fa fa-plus"></i> Add transaction
                    </button>
                </template>
            </WidgetTitleCard>
      </section>

      <BulkSelectionBar
        v-if="selectedItems.length"
        :selected-items="selectedItems"
        @delete-pressed="deleteTransactionsForm.isVisible = true"
        />
    </FinanceTemplate>

    <ConfirmationModal
        :show="deleteTransactionsForm.isVisible"
        @close="deleteTransactionsForm.isVisible = false"
        title="Delete transactions"
        content="Once transactions are deleted, all of its resources and data will be permanently deleted."
    >
        <template #footer>
            <footer class="flex justify-end">
                <LogerButton @click="deleteTransactionsForm.isVisible = false" variant="neutral">
                    Cancel
                </LogerButton>

                <LogerButton
                    type="danger" class="ml-2"
                    @click="deleteBulkTransactions"
                    :class="{ 'opacity-25': deleteTransactionsForm.processing }"
                    :disabled="deleteTransactionsForm.processing">
                    Delete Team
                </LogerButton>
            </footer>
        </template>
    </ConfirmationModal>
  </AppLayout>
</template>


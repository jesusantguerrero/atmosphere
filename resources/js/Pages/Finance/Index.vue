
<script setup lang="ts">
import { computed, toRefs, ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { format, subMonths } from "date-fns";
// @ts-ignore
import { AtButton, AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import WidgetTitleCard from "@/Components/molecules/WidgetTitleCard.vue";

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
    type: Object,
    default: () => ({ spending: 0, total: 0 }),
  },
  income: {
    type: Number,
    default: 0,
  },
  savings: {
    type: Number,
    default: 0,
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
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate
        :title="$t('Finance')"
        :accounts="accounts"
        ref="financeTemplateRef"
    >
      <section class="mt-4 space-y-4">
            <!-- Summary stat cards -->
            <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-base-lvl-3 border border-base rounded-lg p-4 cursor-pointer hover:border-primary/30 transition overflow-hidden"
                    @click="router.visit('/finance/transactions?filter[direction]=DEPOSIT')">
                    <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Income') }}</p>
                    <p class="text-lg font-bold text-green-500 mt-2 break-all leading-tight">{{ formatMoney(income) }}</p>
                    <p class="text-xs text-body-1/40 mt-1">vs {{ lastMonthName }}: <span :class="incomeVariance >= 0 ? 'text-green-500' : 'text-red-400'">{{ incomeVariance }}%</span></p>
                </div>

                <div class="bg-base-lvl-3 border border-base rounded-lg p-4 cursor-pointer hover:border-primary/30 transition overflow-hidden"
                    @click="router.visit('/finance/transactions')">
                    <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Expenses') }}</p>
                    <p class="text-lg font-bold text-red-400 mt-2 break-all leading-tight">{{ formatMoney(transactionTotal) }}</p>
                    <p class="text-xs text-body-1/40 mt-1">vs {{ lastMonthName }}: <span :class="expenseVariance <= 0 ? 'text-green-500' : 'text-red-400'">{{ expenseVariance }}%</span></p>
                </div>

                <div class="bg-base-lvl-3 border border-base rounded-lg p-4 cursor-pointer hover:border-primary/30 transition overflow-hidden"
                    @click="router.visit('/finance/transactions')">
                    <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Savings') }}</p>
                    <p class="text-lg font-bold mt-2 break-all leading-tight" :class="Number(savings) >= 0 ? 'text-green-500' : 'text-red-400'">{{ formatMoney(savings) }}</p>
                    <p class="text-xs text-body-1/40 mt-1">{{ $t('Income') }} - {{ $t('Expenses') }}</p>
                </div>

                <div class="bg-base-lvl-3 border border-base rounded-lg p-4 cursor-pointer hover:border-primary/30 transition overflow-hidden"
                    @click="router.visit('/budgets')">
                    <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Budget') }}</p>
                    <p class="text-lg font-bold text-body mt-2 break-all leading-tight">{{ formatMoney(transactionTotal) }}</p>
                    <BudgetProgress
                        :goal="budgetTotal.spending"
                        :current="transactionTotal"
                        class="h-1.5 rounded-full mt-2"
                        :show-labels="false"
                    />
                    <p class="text-xs text-body-1/40 mt-1">{{ $t('of') }} {{ formatMoney(budgetTotal.spending) }}</p>
                </div>
            </section>

            <section class="grid md:grid-cols-2 gap-4">
                <WidgetTitleCard :title="$t('Planned Transactions')" class="hidden md:block">
                    <TransactionsList
                      class="w-full"
                      table-class="w-full p-2 overflow-auto text-sm rounded-t-lg bg-base-lvl-3"
                      :transactions="planned.slice(0, 5)"
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
                          <span> {{ $t('See scheduled') }}</span>
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

            <WidgetTitleCard :title="$t('Transaction history')" class="w-full">
                <div
                    v-if="!transactions || transactions.length === 0"
                    class="flex flex-col items-center justify-center py-8 w-full"
                >
                    <p class="text-sm text-body-1/50">{{ $t('No transactions for this period.') }}</p>
                </div>
                <TransactionsList
                    v-else
                    class="w-full"
                    table-class="overflow-auto text-sm"
                    :transactions="transactions"
                    :parser="transactionDBToTransaction"
                    @edit="handleEdit"
                />

                <template #action>
                    <LogerButton variant="inverse" class="text-xs" @click="openTransactionModal()">
                        <IMdiPlus class="mr-1" /> {{ $t('Add transaction') }}
                    </LogerButton>
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
        :title="$t('Delete transactions')"
        :content="$t('Once transactions are deleted, all of its resources and data will be permanently deleted.')"
    >
        <template #footer>
            <footer class="flex justify-end">
                <LogerButton @click="deleteTransactionsForm.isVisible = false" variant="neutral">
                    {{ $t('Cancel') }}
                </LogerButton>

                <LogerButton
                    type="danger" class="ml-2"
                    @click="deleteBulkTransactions"
                    :class="{ 'opacity-25': deleteTransactionsForm.processing }"
                    :disabled="deleteTransactionsForm.processing">
                    {{ $t('Delete Transactions') }}
                </LogerButton>
            </footer>
        </template>
    </ConfirmationModal>
  </AppLayout>
</template>


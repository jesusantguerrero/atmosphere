
<script setup lang="ts">
import InfoHint from "@/Components/atoms/InfoHint.vue";
import { computed, toRefs, ref } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { format, subMonths } from "date-fns";
import { es } from "date-fns/locale";
import { formatMonth } from "@/utils";
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
    formatVariance,
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
        const isEs = ((window as any)?.logerLocale ?? 'en').startsWith('es');
        return format(subMonths(pageState.dates.startDate, 1), 'MMM', isEs ? { locale: es } : undefined);
    } catch (e) {
        return 'LM'
    }
})
const incomeVariance = computed(() => {
  return getVariances(props.income, props.lastMonthIncome);
});

const expenseVariance = computed(() => {
  return getVariances(props.transactionTotal, props.lastMonthExpenses);
});

// A null variance means last month had no movement, so there is no baseline to
// compare against — stay neutral instead of implying a good or bad trend.
const incomeVarianceTone = computed(() => {
  if (incomeVariance.value === null) return 'text-body-1/60';
  return Number(incomeVariance.value) >= 0 ? 'text-green-500' : 'text-red-400';
});

const expenseVarianceTone = computed(() => {
  if (expenseVariance.value === null) return 'text-body-1/60';
  return Number(expenseVariance.value) <= 0 ? 'text-green-500' : 'text-red-400';
});

// Fix (Hope): the surplus-first "what's left after paying" number, computed
// directly as income − expenses so it shows without the full ZBB assign ritual.
const availableThisMonth = computed(() => Number(props.income || 0) - Number(props.transactionTotal || 0));

// The ZBB star of the Resumen. Ready to Assign = inflow − everything already
// assigned to categories (currentBudget.total). This IS Hope's 'what's left'
// number, but on the budget's terms: it starts as all your money and drops to 0
// as every peso gets a job. Replaces the old cash-flow 'income − expenses' hero.
const readyToAssign = computed(() => Number(props.income || 0) - Number(currentBudget.value.total || 0));
const allAssigned = computed(() => Number(props.income || 0) > 0 && Math.round(readyToAssign.value) === 0);

// Fix (Hope): `budgetTotal` arrives from the server as an ARRAY of monthly rows
// (getMonthAssignmentTotal groups by month); the current month is the last entry.
// The card previously read `budgetTotal.spending` off that array — always
// `undefined` — so the subtitle was permanently stuck on "No budget set for this
// month" while the headline still rendered `transactionTotal` (raw expenses) as if
// it were a budget, and BudgetProgress divided by a 0 goal. We derive the real
// assigned spending budget and gate the whole card on whether one exists.
const currentBudget = computed(() => {
    const row = Array.isArray(props.budgetTotal)
        ? props.budgetTotal.at(-1)
        : props.budgetTotal;
    return {
        spending: Number(row?.spending ?? 0),
        total: Number(row?.total ?? 0),
    };
});

const hasBudget = computed(() =>
    Number.isFinite(currentBudget.value.spending) && currentBudget.value.spending > 0
);

const topCategories = props.expensesByCategory.slice(0, 4);

// Fix: the Resumen "Transaction history" widget duplicates the Transacciones tab
// and grew too long — cap it to the 5 most recent rows and link out for the rest.
const recentTransactions = computed(() => props.transactions.slice(0, 5));

const { openTransactionModal } = useTransactionModal();
const handleEdit = (transaction: ITransaction) => {
    openTransactionModal({
        transactionData: transaction
    })
}

const selectedItems = ref([]);
const deleteTransactionsForm = useForm({
    isVisible: false,
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
      <FinanceSectionNav />
    </template>

    <FinanceTemplate
        :title="$t('Finance')"
        :accounts="accounts"
        ref="financeTemplateRef"
    >
      <section class="flex justify-end pt-4">
        <AtDatePager
          class="h-12 border-none rounded-md bg-base-lvl-1 text-body"
          v-model:startDate="pageState.dates.startDate"
          v-model:endDate="pageState.dates.endDate"
          @change="executeSearchWithDelay"
          controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
          next-mode="month">
            {{ formatMonth(pageState.dates.startDate, 'MMMM yyyy') }}
        </AtDatePager>
      </section>

      <section class="mt-4 space-y-4">
            <!-- ZBB hero: "Por asignar" (Ready to Assign) = inflow − everything
                 assigned. It's Hope's "what's left" number, but on-brand: it drops
                 to 0 as every peso gets a job, and 0 is celebrated as the win. -->
            <div
                class="p-5 border rounded-lg bg-base-lvl-3"
                :class="allAssigned ? 'border-success/60' : (readyToAssign >= 0 ? 'border-success/40' : 'border-error/40')"
            >
                <p class="text-xs font-medium tracking-wide uppercase text-body-1/50">
                    {{ allAssigned ? $t('This month') : $t('Available this month') }}
                    <InfoHint :title="$t('Ready to assign')" :body="$t('ready_to_assign_hint')" />
                </p>
                <template v-if="allAssigned">
                    <p class="mt-1 text-3xl font-bold leading-tight text-success">✓ {{ $t('All assigned') }}</p>
                    <p class="mt-1 text-xs text-body-1/40">{{ $t('Every peso has a job') }}</p>
                </template>
                <template v-else>
                    <p
                        class="mt-1 text-3xl font-bold leading-tight"
                        :class="readyToAssign >= 0 ? 'text-success' : 'text-error'"
                    >{{ formatMoney(readyToAssign) }}</p>
                    <p class="mt-1 text-xs text-body-1/40">{{ readyToAssign >= 0 ? $t('Ready to assign to your categories') : $t('You assigned more than you have') }}</p>
                </template>
            </div>

            <!-- Summary stat cards -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-base-lvl-3 border border-base rounded-lg p-4 cursor-pointer hover:border-primary/30 transition overflow-hidden"
                    @click="router.visit('/finance/transactions?filter[direction]=DEPOSIT')">
                    <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Income') }}</p>
                    <p class="text-lg font-bold text-green-500 mt-2 leading-tight">{{ formatMoney(income) }}</p>
                    <p class="text-xs text-body-1/40 mt-1">vs {{ lastMonthName }}: <span :class="incomeVarianceTone">{{ formatVariance(incomeVariance) }}</span></p>
                </div>

                <div class="bg-base-lvl-3 border border-base rounded-lg p-4 cursor-pointer hover:border-primary/30 transition overflow-hidden"
                    @click="router.visit('/finance/transactions')">
                    <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Expenses') }}</p>
                    <p class="text-lg font-bold text-red-400 mt-2 leading-tight">{{ formatMoney(transactionTotal) }}</p>
                    <p class="text-xs text-body-1/40 mt-1">vs {{ lastMonthName }}: <span :class="expenseVarianceTone">{{ formatVariance(expenseVariance) }}</span></p>
                </div>

                <div class="bg-base-lvl-3 border border-base rounded-lg p-4 cursor-pointer hover:border-primary/30 transition overflow-hidden"
                    @click="router.visit('/finance/transactions')">
                    <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Savings') }}</p>
                    <p class="text-lg font-bold mt-2 leading-tight" :class="Number(savings) >= 0 ? 'text-green-500' : 'text-red-400'">{{ formatMoney(savings) }}</p>
                    <p class="text-xs text-body-1/40 mt-1">{{ $t('Contributed to savings') }}</p>
                </div>

                <div class="bg-base-lvl-3 border border-base rounded-lg p-4 cursor-pointer hover:border-primary/30 transition overflow-hidden"
                    @click="router.visit('/budgets')">
                    <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('Budget') }}</p>
                    <template v-if="hasBudget">
                        <p class="text-lg font-bold text-body mt-2 leading-tight">{{ formatMoney(transactionTotal) }}</p>
                        <BudgetProgress
                            :goal="currentBudget.spending"
                            :current="transactionTotal"
                            class="h-1.5 rounded-full mt-2"
                            :show-labels="false"
                        />
                        <p class="text-xs text-body-1/40 mt-1">{{ $t('of') }} {{ formatMoney(currentBudget.spending) }}</p>
                    </template>
                    <template v-else>
                        <p class="text-sm font-semibold text-body mt-2">{{ $t('No budget set for this month') }}</p>
                        <p class="text-xs text-primary mt-1">{{ $t('Set your first budget') }} →</p>
                    </template>
                </div>
            </section>

            <Link
                href="/budget-funds/"
                class="flex items-center justify-between gap-3 bg-base-lvl-3 border border-base rounded-lg px-4 py-3 hover:border-primary/30 transition group"
            >
                <div class="flex items-center gap-3">
                    <div class="bg-primary/10 rounded-full w-9 h-9 flex items-center justify-center">
                        <i class="fas fa-shield-alt text-primary"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-body">{{ $t('Emergency Fund Builder') }} <InfoHint :title="$t('Emergency fund')" :body="$t('emergency_fund_hint')" /></p>
                        <p class="text-xs text-body-1/50">{{ $t('Plan and track your safety net') }}</p>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-body-1/40 group-hover:text-primary transition"></i>
            </Link>

            <section class="grid md:grid-cols-2 gap-4">
                <WidgetTitleCard v-if="planned.length" :title="$t('Planned Transactions')" class="hidden md:block">
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
                <div
                    v-else
                    class="hidden md:flex md:self-start items-center px-4 py-3 text-sm text-body-1/50 bg-base-lvl-3 border border-base rounded-lg"
                >
                    {{ $t('No planned transactions yet') }}
                </div>


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
                <div v-else class="w-full">
                    <TransactionsList
                        class="w-full"
                        table-class="overflow-auto text-sm"
                        :transactions="recentTransactions"
                        :parser="transactionDBToTransaction"
                        @edit="handleEdit"
                    />
                    <div v-if="transactions.length > 5" class="flex justify-end pt-2">
                        <Link
                            href="/finance/transactions"
                            class="flex items-center text-xs text-primary hover:underline"
                        >
                            {{ $t('See all') }}
                            <i class="ml-1 fa fa-chevron-right"></i>
                        </Link>
                    </div>
                </div>

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


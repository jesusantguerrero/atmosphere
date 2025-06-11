<script setup lang="ts">
import { computed, toRefs, provide, ref, onMounted, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import { IServerSearchData } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";

import FinanceTemplate from "../Partials/FinanceTemplate.vue";
import FinanceSectionNav from "../Partials/FinanceSectionNav.vue";
import TransactionSearch from "@/domains/transactions/components/TransactionSearch.vue";
import TransactionTable from "@/domains/transactions/components/TransactionTable.vue";

import { useTransactionModal } from "@/domains/transactions";
// import { IServerSearchData, useServerSearch } from "@/composables/useServerSearch";
import { reconciliationCols } from "@/domains/transactions";
import { useAppContextStore } from "@/store";
import { IAccount, ICategory, ITransaction } from "@/domains/transactions/models";
import { formatMoney } from "@/utils";
import AccountReconciliationAlert from "@/domains/transactions/components/AccountReconciliationAlert.vue";

const { openTransactionModal } = useTransactionModal();

const props = withDefaults(defineProps<{
    transactions: ITransaction[];
    lastReconciliation: ITransaction;
    reconciliations: ITransaction[];
    account: IAccount;
    accounts: IAccount[];
    categories: ICategory[],
    serverSearchOptions: Partial<IServerSearchData>,
    accountId?: number,
}>(), {
    serverSearchOptions: () => {
        return {}
    }
});

const isLoading = ref(false);
const { serverSearchOptions, accountId, accounts } = toRefs(props);

provide("selectedAccountId", accountId);


const context = useAppContextStore();
const listComponent = computed(() => {
  return context.isMobile ? TransactionSearch : TransactionTable;
});

const removeReconciliation = (transaction: ITransaction) => {
  router.delete(`/finance/reconciliation/${transaction.id}`, {
    onSuccess() {
      router.reload();
    },
  });
};

const findLinked = (transaction: ITransaction) => {
  router.patch(`/finance/reconciliation/${transaction.id}/linked`, {
    // @ts-ignore
    onSuccess() {
      router.reload();
    },
  });
};

const handleEdit = (transaction: ITransaction) => {
    axios.get(`/finance/recociliation/${transaction.id}?json=true`).then(({ data }) => {
        openTransactionModal({
            transactionData: data,
        });
    })
};

// reconciliation

const isEditing = ref(false);
const statementBalanceRef = ref()

onMounted(() => {
    router.on('start', () => isLoading.value = true)
    router.on('finish', () => isLoading.value = false)
})

const showAll = ref(false);
const visibleReconciliations = computed(() => {
  return showAll.value ? props.reconciliations.reverse() : props.reconciliations.slice(0, 4).reverse();
});

const lastReconciliationDate = computed(() => {
  return visibleReconciliations.value.at(-1)?.date;
});

const hasPendingReconciliation = computed(() => {
  return props.reconciliations.some((reconciliation: any) => reconciliation.status !== 'completed');
});

</script>

<template>
  <AppLayout @back="router.visit('/finance/transactions')" :title="account.name" :show-back-button="true">
    <template #header>
      <FinanceSectionNav />
    </template>
    <template #title>
        <section class="flex items-center">
            <h4
                @click="router.visit(`/finance/accounts/${account.id}/`)"
                title="reconciliations"
                class="flex items-center ml-2 font-bold cursor-pointer text-secondary"
            >
            <IMdiWallet class="mr-2" />
            <span>
                {{ account.name }}
            </span>
            </h4>
        </section>
    </template>
    <FinanceTemplate title="Transactions" :accounts="accounts">
      <section class="bg-base-lvl-3 p-6 rounded-lg shadow mt-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-xl font-bold">{{ account.name }}</h2>
            <div class="mt-1">
              <span class="bg-gray-200 text-gray-700 px-3 py-1 rounded font-semibold text-sm">
                Reconciled up to {{ reconciliations.length ? lastReconciliationDate : '' }}
              </span>
            </div>
          </div>
          <div class="flex items-center space-x-6">
            <span class="text-2xl font-bold text-right">{{ formatMoney(account.balance) }}</span>
            <button class="bg-blue-600 text-white px-6 py-2 rounded-full font-bold shadow hover:bg-blue-700 transition">
              Reconcile new transactions
            </button>
          </div>
        </div>

        <!-- Timeline -->
        <div class="relative flex items-center justify-between mt-8 mb-4 overflow-x-auto">
          <!-- Horizontal line -->
          <div class="absolute left-0 right-0 top-1/2 h-0.5 bg-gray-200 z-0" style="transform: translateY(-50%);"></div>
          <!-- Timeline items -->
          <template v-for="(reconciliation, idx) in visibleReconciliations" :key="reconciliation.id">
            <div class="flex flex-col items-center z-10 min-w-[120px]">
              <!-- Completed icon -->
              <div class="bg-white rounded-full border-4 w-12 h-12 flex items-center justify-center mb-2 shadow"
              :class="{ 'border-green-400': reconciliation.status === 'completed', 'border-gray-400': reconciliation.status !== 'completed' }"
              >
                <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" v-if="reconciliation.status === 'completed'">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <AccountReconciliationAlert v-else />
              </div>
              <span class="text-gray-600 font-medium mt-1">{{ reconciliation.date }}</span>
              <span class="text-gray-400 font-medium mt-1" v-if="reconciliation.status !== 'completed'">{{ reconciliation.total_transactions }} new transactions</span>
            </div>
            <!-- Connector (except after last) -->
            <div v-if="idx < visibleReconciliations.length - 1" class="flex-1"></div>
          </template>
          <!-- New period icon -->
          <div class="flex flex-col items-center z-10 min-w-[120px]" v-if="!hasPendingReconciliation">
            <div class="bg-white rounded-full border-2 border-gray-400 w-12 h-12 flex items-center justify-center mb-2 shadow">
              <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="6" />
                <line x1="12" y1="9" x2="12" y2="15" />
                <line x1="9" y1="12" x2="15" y2="12" />
              </svg>
            </div>
            <span class="text-gray-400 font-medium mt-1">{{ transactions.length }} new transaction</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center space-x-6 mt-4">
          <button class="text-blue-700 font-bold hover:underline" @click="showAll = !showAll">Show all periods</button>
          <button class="text-blue-700 font-bold hover:underline">Add period</button>
        </div>
      </section>
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup lang="ts">
import { AtBackgroundIconCard, AtDatePager } from "atmosphere-ui";
import { computed, toRefs, provide, ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";
import AppSearch from "@/Components/AppSearch/AppSearch.vue";
import TransactionSearch from "@/Components/templates/TransactionSearch.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import TransactionTemplate from "@/Components/templates/TransactionTemplate.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import DraftButtons from "@/Components/DraftButtons.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";

import { useTransactionModal } from "@/domains/transactions";
import { useServerSearch } from "@/composables/useServerSearch";
import { tableAccountCols } from "@/domains/transactions";
import { useAppContextStore } from "@/store";
import { formatMoney } from "@/utils";
import { ITransaction } from "@/domains/transactions/models";
import { format } from "date-fns";

const { openTransactionModal } = useTransactionModal();

const props = defineProps({
  transactions: {
    type: Object,
    default: () => ({
      data: [],
    }),
  },
  stats: {
    type: Object,
    default: () => ({
      data: [],
    }),
  },
  accounts: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default() {
      return [];
    },
  },
  serverSearchOptions: {
    type: Object,
    default: () => ({}),
  },
  accountId: {
    type: [Number, null],
  },
});

const { serverSearchOptions, accountId, accounts } = toRefs(props);
const { state: pageState, hasFilters,executeSearchWithDelay, executeSearch, reset } =
useServerSearch(serverSearchOptions);

provide("selectedAccountId", accountId);

const selectedAccount = computed(() => {
  return accounts.value.find((account) => account.id === accountId.value);
});

const context = useAppContextStore();
const listComponent = computed(() => {
  return context.isMobile ? TransactionSearch : TransactionTemplate;
});

const isDraft = computed(() => {
  return serverSearchOptions.value.filters?.status == "draft";
});

const removeTransaction = (transaction: ITransaction) => {
  router.delete(`/transactions/${transaction.id}`, {
    onSuccess() {
      router.reload();
    },
  });
};

const findLinked = (transaction: ITransaction) => {
  router.patch(`/transactions/${transaction.id}/linked`, {
    onSuccess() {
      router.reload();
    },
  });
};

const handleEdit = (transaction: ITransaction) => {
  openTransactionModal({
    transactionData: transaction,
  });
};

const reconciliation = () => {
  const current = prompt("Whats your current amount?");
  const total = selectedAccount.value?.balance - current;
  openTransactionModal({
    mode: "Deposit",
    transactionData: {
      category_id: "",
      total: total,
    },
  });
};

const isLoading = ref(false);
onMounted(() => {
    router.on('start', () => isLoading.value = true)
    router.on('finish', () => isLoading.value = false)
})

const monthName = computed(() => format(pageState.dates.startDate, "MMMM"))
</script>

<template>
  <AppLayout @back="router.visit('/finance/transactions')" :show-back-button="true">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <div class="flex items-center w-full space-x-2">
            <AtDatePager
              class="w-full h-12 border-none bg-base-lvl-1 text-body"
              v-model:startDate="pageState.dates.startDate"
              v-model:endDate="pageState.dates.endDate"
              controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
              next-mode="month"
            />
            <LogerButton variant="inverse" @click="reconciliation()">
              Reconciliation
            </LogerButton>
            <DraftButtons v-if="isDraft" />
          </div>
        </template>
      </FinanceSectionNav>
    </template>
    <FinanceTemplate title="Transactions" :accounts="accounts">
      <div class="flex mt-4 space-x-4">
        <AtBackgroundIconCard
          class="w-full cursor-pointer text-body-1 bg-base-lvl-3"
          v-for="stat in stats"
          :value="formatMoney(stat)"
        />
      </div>

      <section class="mt-4 bg-base-lvl-3">
        <header class="flex items-center justify-between px-6 py-2">
            <section>
                <h4 class="text-lg font-bold text-body-1">
                    All transactions in <span class="text-secondary">
                        {{ monthName }}
                    </span>
                </h4>
                <AppSearch
                    v-model.lazy="pageState.search"
                    class="w-full md:flex"
                    :has-filters="hasFilters"
                    @clear="reset()"
                    @blur="executeSearch"
                />
            </section>
        <span>
            {{  transactions.length }} Results
        </span>
        </header>
          <Component
            :is="listComponent"
            :cols="tableAccountCols(props.accountId)"
            :transactions="transactions"
            :server-search-options="serverSearchOptions"
            :is-loading="isLoading"
            @findLinked="findLinked"
            @removed="removeTransaction"
            @edit="handleEdit"
          />
      </section>
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, toRefs } from "vue";
import { router } from "@inertiajs/vue3";
import { AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";

import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";
import FinanceTemplate from "./Partials/FinanceTemplate.vue";
import TransactionSearch from "@/domains/transactions/components/TransactionSearch.vue";
import TransactionTable from "@/domains/transactions/components/TransactionTable.vue";
import DraftButtons from "@/domains/transactions/components/DraftButtons.vue";

import { useTransactionModal } from "@/domains/transactions";
import { useServerSearch, IServerSearchData } from "@/composables/useServerSearchV2";
import { tableAccountCols } from "@/domains/transactions";
import { useAppContextStore } from "@/store";
import { ICategory, ITransaction, IAccount } from "@/domains/transactions/models";

const { openTransactionModal } = useTransactionModal();

const props = defineProps<{
  transactions: ITransaction[],
  accounts: IAccount,
  categories: ICategory,
  serverSearchOptions: Partial<IServerSearchData>,
  accountId: number,
}>();

const { serverSearchOptions } = toRefs(props);
const { state: pageState, hasFilters, reset } = useServerSearch(serverSearchOptions, {
    defaultDates: true,
});

const context = useAppContextStore();
const listComponent = computed(() => {
  return context.isMobile ? TransactionSearch : TransactionTable;
});

const isDraft = computed(() => {
  return serverSearchOptions.value?.filters?.status == "draft";
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

const transactionStatus = {
  draft: {
    label: "Drafts",
    value: "/finance/transactions?filter[status]=draft&relationships=linked",
  },
  verified: {
    label: "Verified",
    value: "/finance/transactions?",
  },
  scheduled: {
    label: "Scheduled",
    value: "/finance/transactions?filter[status]=scheduled",
  },
};
</script>


<template>
  <AppLayout @back="router.visit('/finance/transactions')" :show-back-button="true">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <div class="flex items-center w-full space-x-2">
            <LogerButton
              variant="inverse"
              class=""
              v-for="(item, statusName) in transactionStatus"
              :key="statusName"
              @click="router.visit(item.value)"
            >
              {{ item.label }}
            </LogerButton>
            <AtDatePager
              class="w-full h-12 border-none bg-base-lvl-1 text-body"
              v-model:startDate="pageState.dates.startDate"
              v-model:endDate="pageState.dates.endDate"
              controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
              next-mode="month"
            />
            <LogerButton variant="inverse">
                Import Transactions
            </LogerButton>
            <DraftButtons v-if="isDraft" />
          </div>
        </template>
      </FinanceSectionNav>
    </template>
    <FinanceTemplate title="Transactions" :accounts="accounts">
      <Component
        :is="listComponent"
        :cols="tableAccountCols(props.accountId)"
        :transactions="transactions"
        :server-search-options="serverSearchOptions"
        @findLinked="findLinked"
        @removed="removeTransaction"
        @edit="handleEdit"
      />
    </FinanceTemplate>
  </AppLayout>
</template>

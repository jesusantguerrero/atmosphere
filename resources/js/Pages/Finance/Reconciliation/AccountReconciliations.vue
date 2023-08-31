<script setup lang="ts">
import { computed, toRefs, provide, ref, onMounted, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import { AtBackgroundIconCard, IServerSearchData, AtField } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";

import FinanceTemplate from "../Partials/FinanceTemplate.vue";
import FinanceSectionNav from "../Partials/FinanceSectionNav.vue";
import TransactionSearch from "@/domains/transactions/components/TransactionSearch.vue";
import TransactionTemplate from "@/domains/transactions/components/TransactionTemplate.vue";

import { useTransactionModal } from "@/domains/transactions";
// import { IServerSearchData, useServerSearch } from "@/composables/useServerSearch";
import { reconciliationCols } from "@/domains/transactions";
import { useAppContextStore } from "@/store";
import { formatMoney } from "@/utils";
import { IAccount, ICategory, ITransaction } from "@/domains/transactions/models";

const { openTransactionModal } = useTransactionModal();


interface CollectionData<T> {
    data: T[]
}
const props = withDefaults(defineProps<{
    transactions: ITransaction[];
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
  return context.isMobile ? TransactionSearch : TransactionTemplate;
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
    // @ts-ignore
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

// reconciliation

const isEditing = ref(false);
const statementBalanceRef = ref()
const toggleEditing = () => {
    isEditing.value = !isEditing.value
    if (isEditing.value) {
        nextTick(() => {
            statementBalanceRef.value.focus()
        })
    }
}

onMounted(() => {
    router.on('start', () => isLoading.value = true)
    router.on('finish', () => isLoading.value = false)
})

</script>

<template>
  <AppLayout @back="router.visit('/finance/transactions')" :title="account.name" :show-back-button="true">
    <template #header>
      <FinanceSectionNav />
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
          <Component
            :is="listComponent"
            :cols="reconciliationCols()"
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

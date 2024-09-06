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

const { openTransactionModal } = useTransactionModal();

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
      <section class=" bg-base-lvl-3">
          <Component
            :is="listComponent"
            :cols="reconciliationCols()"
            :transactions="transactions"
            :server-search-options="serverSearchOptions"
            :is-loading="isLoading"
            @findLinked="findLinked"
            @removed="removeReconciliation"
            @edit="handleEdit"
          />
      </section>
    </FinanceTemplate>
  </AppLayout>
</template>

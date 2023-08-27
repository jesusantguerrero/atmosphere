<script setup lang="ts">
import { computed, toRefs, provide, ref, onMounted, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import { AtBackgroundIconCard, AtDatePager, useServerSearch, IServerSearchData, AtField } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import AppSearch from "@/Components/AppSearch/AppSearch.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";

import FinanceTemplate from "../Partials/FinanceTemplate.vue";
import FinanceSectionNav from "../Partials/FinanceSectionNav.vue";
import TransactionSearch from "@/domains/transactions/components/TransactionSearch.vue";
import TransactionTemplate from "@/domains/transactions/components/TransactionTemplate.vue";

import { useTransactionModal } from "@/domains/transactions";
// import { IServerSearchData, useServerSearch } from "@/composables/useServerSearch";
import { tableAccountCols } from "@/domains/transactions";
import { useAppContextStore } from "@/store";
import { formatMoney } from "@/utils";
import { IAccount, ICategory, ITransaction } from "@/domains/transactions/models";

const { openTransactionModal } = useTransactionModal();


interface CollectionData<T> {
    data: T[]
}
const props = withDefaults(defineProps<{
    transactions: ITransaction[];
    stats: CollectionData<Record<string, number>>;
    account: IAccount;
    accounts: IAccount[];
    reconciliation: Record<string, any>
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
const { state: pageState, hasFilters, executeSearch, reset } =
useServerSearch(serverSearchOptions);

provide("selectedAccountId", accountId);

const selectedAccount = computed(() => {
  return accounts.value.find((account) => account.id === accountId?.value);
});

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

const reconciliation = () => {
  const current: number = Number(prompt("Whats your current amount?"));
  const total = (selectedAccount.value?.balance || 0) - current;
  openTransactionModal({
    mode: "Deposit",
    transactionData: {
      category_id: "",
      total: total,
    },
  });
};

onMounted(() => {
    router.on('start', () => isLoading.value = true)
    router.on('finish', () => isLoading.value = false)
})

const isEditing = ref(false);
const statementBalance = ref(props.reconciliation.amount)
const statementBalanceRef = ref()
const toggleEditing = () => {
    isEditing.value = !isEditing.value
    if (isEditing.value) {
        nextTick(() => {
            statementBalanceRef.value.focus()
        })
    }
}
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
        <header class="flex items-center justify-between px-6 py-2">
          
              <AtField label="transaction matched">
                 0 of {{  transactions.length }}
              </AtField>
         
              <AtField label="Statement balance">
                <LogerInput
                    ref="input"
                    class="opacity-100 cursor-text"
                    v-model="statementBalance"
                    :number-format="true"
                    :disabled="!isEditing"
                    @blur="isEditing = false"
                   
                >
                    <template #prefix>
                        {{ account.currency_code }}
                    </template>
                    <template #suffix>
                        <IMdiPencil class="cursor-pointer"  @click.prevent="toggleEditing" />
                    </template>
                </LogerInput>
              </AtField>

              <AtField label="Loger balance">
                {{  formatMoney(account.balance, account.currency_code ) }}
             </AtField>
              <AtField label="Difference">
                <span class="font-bold">
                    {{  formatMoney(account.balance - statementBalance) }}
                </span>
             </AtField>
             <LogerButton variant="inverse" @click="reconciliation()">
                Complete
              </LogerButton>
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

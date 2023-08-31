<script setup lang="ts">
import { computed, toRefs, provide, ref, onMounted, nextTick } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { AtBackgroundIconCard, AtField } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";

import LogerButton from "@/Components/atoms/LogerButton.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";

import FinanceTemplate from "../Partials/FinanceTemplate.vue";
import FinanceSectionNav from "../Partials/FinanceSectionNav.vue";
import TransactionSearch from "@/domains/transactions/components/TransactionSearch.vue";
import TransactionTemplate from "@/domains/transactions/components/TransactionTemplate.vue";

import { useTransactionModal } from "@/domains/transactions";
import { IServerSearchData, useServerSearch } from "@/composables/useServerSearchV2";
import { tableAccountCols } from "@/domains/transactions";
import { useAppContextStore } from "@/store";
import { formatMoney } from "@/utils";
import { IAccount, ICategory, ITransaction } from "@/domains/transactions/models";
import { NPagination } from "naive-ui";

const { openTransactionModal } = useTransactionModal();

interface CollectionData<T> {
  data: T[];
}
const props = withDefaults(
  defineProps<{
    transactions: ITransaction[];
    stats: CollectionData<Record<string, number>>;
    account: IAccount;
    accounts: IAccount[];
    reconciliation: Record<string, any>;
    categories: ICategory[];
    serverSearchOptions: Partial<IServerSearchData>;
    accountId?: number;
  }>(),
  {
    serverSearchOptions: () => {
      return {};
    },
  }
);

const isLoading = ref(false);
const { serverSearchOptions, accountId, accounts } = toRefs(props);

provide("selectedAccountId", accountId);

const { state } = useServerSearch(serverSearchOptions)

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

const reconcileForm = useForm({
  date: props.reconciliation.date,
  balance: props.reconciliation.amount,
});

const isEditing = ref(false);
const statementBalanceRef = ref();
const toggleEditing = () => {
  isEditing.value = !isEditing.value;
  if (isEditing.value) {
    nextTick(() => {
      statementBalanceRef.value.focus();
    });
  }
};

const completeReconciliation = () => {
  reconcileForm
    .transform((data) => ({
      ...data,
      date: props.reconciliation.date,
    }))
    .put(`/finance/reconciliation/${props.reconciliation.id}`, {
      onFinish() {
        reconcileForm.reset();
        reconcileForm.isVisible = false;
      },
    });
};
onMounted(() => {
  router.on("start", () => (isLoading.value = true));
  router.on("finish", () => (isLoading.value = false));
});

const transactionList = computed(() => {
  return props.transactions.data;
});
</script>

<template>
  <AppLayout
    @back="router.visit('/finance/transactions')"
    :title="account.name"
    :show-back-button="true"
  >
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
          <AtField label="transaction matched"> 0 of {{ transactions.total }} </AtField>

          <AtField label="Statement balance">
            <LogerInput
              ref="input"
              class="opacity-100 cursor-text"
              v-model="reconcileForm.balance"
              :number-format="true"
              :disabled="!isEditing"
              @blur="isEditing = false"
            >
              <template #prefix>
                {{ account.currency_code }}
              </template>
              <template #suffix>
                <IMdiPencil class="cursor-pointer" @click.prevent="toggleEditing" />
              </template>
            </LogerInput>
          </AtField>

          <AtField label="Loger balance">
            {{ formatMoney(account.balance, account.currency_code) }}
          </AtField>
          <AtField label="Difference">
            <span class="font-bold">
              {{ formatMoney(account.balance - reconcileForm.balance) }}
            </span>
          </AtField>
          <LogerButton
            variant="inverse"
            v-if="reconciliation.status != 'completed'"
            @click="completeReconciliation()"
            :processing="reconcileForm.processing"
            :disabled="!reconcileForm.balance"
          >
            Complete
          </LogerButton>
        </header>
        <Component
          :is="listComponent"
          :cols="tableAccountCols(props.reconciliation.account_id)"
          :transactions="transactionList"
          :server-search-options="serverSearchOptions"
          :is-loading="isLoading"
          @findLinked="findLinked"
          @removed="removeTransaction"
          @edit="handleEdit"
        />

        <NPagination v-model:page="state.page" :page-count="Math.ceil(transactions.total / 25)" />
      </section>
    </FinanceTemplate>
  </AppLayout>
</template>

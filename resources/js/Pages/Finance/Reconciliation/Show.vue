<script setup lang="ts">
import { computed, toRefs, provide, ref, onMounted, nextTick } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { AtBackgroundIconCard, AtField } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";

import LogerButton from "@/Components/atoms/LogerButton.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";

import FinanceTemplate from "../Partials/FinanceTemplate.vue";
import FinanceSectionNav from "../Partials/FinanceSectionNav.vue";
import ReconciliationTable from "@/domains/transactions/components/ReconciliationTable.vue";

import { useTransactionModal } from "@/domains/transactions";
import { IServerSearchData, useServerSearch } from "@/composables/useServerSearchV2";
import { tableAccountCols } from "@/domains/transactions";
import { formatMoney } from "@/utils";
import { IAccount, ICategory, ITransaction } from "@/domains/transactions/models";
import { NPagination } from "naive-ui";
import axios from "axios";

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


interface ReconciliationEntry {
    entry_id: number
    transaction_id: number
    is_matched: boolean
}

const removeTransaction = (transaction: ReconciliationEntry) => {
  router.delete(`/transactions/${transaction.transaction_id}`, {
    onSuccess() {
      router.reload();
    },
  });
};



const toggleCheck = (entry: ReconciliationEntry) => {
  router.put(`/finance/reconciliation/${props.reconciliation.id}/reconciliation-entries/${entry.entry_id}/check`, {
    matched: !Boolean(entry.is_matched),
  }, {
    preserveScroll: true,
    preserveState: true,
    only: ['transactions'],
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
    axios.get(`/transactions/${transaction.transaction_id}?json=true`).then(({ data }) => {
        openTransactionModal({
            transactionData: data,
        });
    })
};

// reconciliation


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

const reconcileForm = useForm({
  date: props.reconciliation.date,
  balance: props.reconciliation.amount,
});

const completeReconciliation = () => {
    if (reconcileForm.processing) return
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

const syncReconciliationForm = useForm({});
const syncReconciliation = async () => {
    if (syncReconciliationForm.processing) return
    syncReconciliationForm
        .put(`/finance/reconciliation/${props.reconciliation.id}/sync-transactions`, {
        only: ['transactions'],
            preserveScroll: true,
            preserveState: true,
        });
};

const deleteReconciliation = async () => {
    const canDelete = confirm("Are you sure you want to delete this?")

    if (canDelete) {
        router
          .delete(`/finance/reconciliation/${props.reconciliation.id}`, {
           only: ['transactions'],
              preserveScroll: true,
              preserveState: true,
              onSuccess() {
                router.visit(`/finance/accounts/${props.reconciliation.account_id}`)
              }
          });
    }
};
onMounted(() => {
  router.on("start", () => (isLoading.value = true));
  router.on("finish", () => (isLoading.value = false));
});

const transactionList = computed(() => {
  return props.transactions.data;
});

const transactionsMatched = computed(() => {
  return props.transactions.data.filter(item => item.matched).length;
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

      <section class=" bg-base-lvl-3">
        <header class="flex items-center justify-between px-6 py-2">
          <AtField label="transaction matched"> {{ transactionsMatched }} of {{ transactions.total }} </AtField>

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
          <section class="flex items-center space-x-1">
              <LogerButton
                variant="neutral"
                v-if="reconciliation.status != 'completed'"
                @click="completeReconciliation()"
                :processing="reconcileForm.processing"
                :disabled="!reconcileForm.balance"
              >
                <IMdiCheck />
              </LogerButton>
              <LogerButton
                variant="neutral"
                v-if="reconciliation.status != 'completed'"
                @click="syncReconciliation()"
                :processing="syncReconciliationForm.processing || reconcileForm.processing"
                title="sync transactions"
              >
                <IMdiSync :class="{'animate-spin': syncReconciliationForm.processing}" />
              </LogerButton>
              <LogerButton
                variant="error"
                v-if="reconciliation.status != 'completed'"
                @click="deleteReconciliation()"
                :processing="syncReconciliationForm.processing || reconcileForm.processing"
                title="delete reconciliation"
              >
                <IMdiTrash />
              </LogerButton>
          </section>
        </header>
        <section class="flex justify-between px-4 py-2 bg-base-lvl-2">
            <article class="flex items-center space-x-2" v-if="false">
                <LogerButton variant="inverse">
                    Merge
                </LogerButton>
                <LogerButton variant="inverse">
                    Delete
                </LogerButton>
            </article>
            <article class="flex items-center justify-end w-full space-x-2" v-if="false">
                <LogerButton variant="inverse"   v-if="reconciliation.status != 'completed'">
                    Add transaction
                </LogerButton>
                <LogerButton variant="inverse">
                    Sort
                </LogerButton>
                <LogerButton variant="inverse">
                    Filters
                </LogerButton>
                <LogerButton variant="inverse">
                    Search
                </LogerButton>
            </article>
            <article></article>
        </section>
        <ReconciliationTable
          :cols="tableAccountCols(props.reconciliation.account_id)"
          :transactions="transactionList"
          :server-search-options="serverSearchOptions"
          :is-loading="isLoading"
          @toggleCheck="toggleCheck"
          @findLinked="findLinked"
          @removed="removeTransaction"
          @edit="handleEdit"
        >
          <template #footer>
              <footer class="justify-end flex px-4 mt-4">
                  <NPagination v-model:page="state.page" :page-count="Math.ceil(transactions.total / 25)" />
              </footer>
        </template>
        </ReconciliationTable>
      </section>
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup lang="ts">
import { reactive, ref } from "vue";
import { router } from "@inertiajs/vue3";

import TransactionsTable from "@/domains/transactions/components/TransactionsList.vue";
import { ITransaction } from "@/domains/transactions/models/transactions";

import { fromDBToAllAccounts, transactionDBToTransaction } from "@/domains/transactions";
import InlineTransactionCategory from './InlineTransactionCategory.vue';

const emit = defineEmits(['categorized']);

const props = defineProps({
  transactions: {
    type: Array,
    default: () => [],
  },
  title: {
    type: String,
    default: "s",
  },
  withTeleport: {
    type: Boolean,
    default: false,
  },
  allAccounts: {
    type: Boolean
  }
});

const isTransferModalOpen = ref(false);
const transferConfig = reactive<{
    recurrence: boolean;
    automatic: boolean;
    transactionData: null | Record<string, any>
}>({
  recurrence: false,
  automatic: false,
  transactionData: null,
});

const handleEdit = (transaction: ITransaction) => {
  transferConfig.transactionData = transaction;
  isTransferModalOpen.value = true;
};

const removeTransaction = (transaction: ITransaction) => {
    if (confirm("Are you sure you want to remove this transaction?")) {
        router.delete(`/transactions/${transaction.id}`, {
            onSuccess() {
                router.reload();
            }
        })
    }
}

const parser = props.allAccounts ? fromDBToAllAccounts : transactionDBToTransaction;
</script>

<template>
  <div class="pb-20">
    <TransactionsTable
      table-label=""
      table-class="overflow-auto border rounded-lg shadow-md bg-base-lvl-1"
      show-sum
      allow-select
      allow-remove
      allow-edit
      :all-accounts="allAccounts"
      :transactions="transactions"
      :parser="parser"
      @edit="handleEdit"
      @removed="removeTransaction"
    >
      <template #after-row="{ transaction }">
        <div v-if="transaction.category_line_id" class="border-b border-body-1/10 px-4 pb-3">
          <InlineTransactionCategory :key="`${transaction.id}-${transaction.category_id}`" :transaction="transaction" @saved="emit('categorized')" />
        </div>
      </template>
    </TransactionsTable>
  </div>
</template>


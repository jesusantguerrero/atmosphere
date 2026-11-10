<script setup lang="ts">
import { getCurrentInstance } from "vue";

import TransactionsTable from "@/domains/transactions/components/TransactionsList.vue";
import { ITransaction } from "@/domains/transactions/models/transactions";

import { fromDBToAllAccounts, removeTransaction as removeWithConfirm, transactionDBToTransaction, useTransactionModal } from "@/domains/transactions";
import InlineTransactionCategory from './InlineTransactionCategory.vue';

const emit = defineEmits(['categorized', 'edit', 'removed']);

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

// Pages that list transactions handle edit/remove themselves; the ones that
// don't still get the global modal and the shared confirm-then-delete.
const instance = getCurrentInstance();
const parentListens = (event: string): boolean => !!instance?.vnode.props?.[event];
const { openTransactionModal } = useTransactionModal();

const handleEdit = (transaction: ITransaction) => {
  if (parentListens('onEdit')) {
    emit('edit', transaction);
    return;
  }
  openTransactionModal({ transactionData: transaction });
};

const removeTransaction = (transaction: ITransaction) => {
  if (parentListens('onRemoved')) {
    emit('removed', transaction);
    return;
  }
  removeWithConfirm(transaction);
};

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


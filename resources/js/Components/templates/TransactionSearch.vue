<script setup lang="ts">
import { reactive, ref } from "vue";
import { router } from "@inertiajs/vue3";

import TransactionsTable from "@/Components/organisms/TransactionsTable.vue";
import { fromDBToAllAccounts, transactionDBToTransaction } from "@/domains/transactions";
import { ITransaction } from "@/domains/transactions/models/transactions";

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
    />
  </div>
</template>


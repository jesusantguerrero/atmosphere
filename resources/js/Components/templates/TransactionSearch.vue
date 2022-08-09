<template>
  <div class="pb-20 mt-5">
    <TransactionsTable
      table-label=""
      class="pt-3 mt-5"
      table-class="overflow-auto bg-base-lvl-1 border rounded-lg shadow-md mt-5"
      allow-select
      show-sum
      allow-remove
      allow-edit
      :transactions="transactions"
      :parser="transactionDBToTransaction"
      @edit="handleEdit"
      @removed="removeTransaction"
    />
  </div>
</template>

<script setup>
import { reactive, ref, watch } from "vue";
import { Inertia } from "@inertiajs/inertia";
import TransactionsTable from "@/Components/organisms/TransactionsTable.vue";
import TransactionModal from "../TransactionModal.vue";
import { transactionDBToTransaction } from "@/domains/transactions";

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
});

const isTransferModalOpen = ref(false);
const transferConfig = reactive({
  recurrence: false,
  automatic: false,
  transactionData: null,
});

const handleEdit = (transaction) => {
  transferConfig.transactionData = transaction;
  isTransferModalOpen.value = true;
};

const importTransaction = () => {
  const file = document.querySelector('input[type="file"]').files[0];
  Inertia.post("/financial/import", {
    file,
  });
};

const removeTransaction = (transaction) => {
    if (confirm("Are you sure you want to remove this transaction?")) {
        Inertia.delete(`/transactions/${transaction.id}`, {
            onSuccess() {
                Inertia.reload();
            }
        })
    }
}
</script>

<template>
  <AppLayout @back="$inertia.visit(route('finance'))" :show-back-button="true">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <div class="flex items-center w-full space-x-2">
            <!-- <AtDatePager
              class="w-full h-12 border-none bg-base-lvl-1 text-body"
              v-model="pageState.date"
              v-model:dateSpan="pageState.dateSpan"
              v-model:startDate="pageState.searchOptions.date.startDate"
              v-model:endDate="pageState.searchOptions.date.endDate"
              controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
              next-mode="month"
            /> -->
          </div>
        </template>
      </FinanceSectionNav>
    </template>
    <FinanceTemplate title="Transactions" :accounts="accounts">
      <Component
        :is="listComponent"
        :transactions="transactions.data"
        :server-search-options="serverSearchOptions"
        @removed="removeTransaction"
        @edit="handleEdit"
      />
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup>
import { NSelect } from "naive-ui";
import { AtDatePager } from "atmosphere-ui";
import { computed, toRefs} from "vue";
import { Inertia } from "@inertiajs/inertia";

import AppLayout from "@/Layouts/AppLayout.vue";
import TransactionSearch from "@/Components/templates/TransactionSearch.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import TransactionTemplate from "@/Components/templates/TransactionTemplate.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";

import { useTransferModal } from "@/utils/useTransferModal";


const { openTransferModal } = useTransferModal();

const props = defineProps({
  transactions: {
    type: Array,
    default: () => [],
  },
  accounts: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default() {
        return []
    }
  },
  serverSearchOptions: {
    type: Object,
    default: () => ({}),
  },
  accountId: {
    type: [Number, null],
  },
});

const { serverSearchOptions } = toRefs(props);

const isAccountTransactions = computed(() => {
    return props.accountId;
})

const listComponent = computed(() => {
  return TransactionTemplate;
});

const removeTransaction = (transaction) => {
    Inertia.delete(`/transactions/${transaction.id}`, {
        onSuccess() {
            Inertia.reload()
        }
    })
}

const handleEdit = (transaction) => {
    openTransferModal({
        transactionData: transaction
    })
}
</script>

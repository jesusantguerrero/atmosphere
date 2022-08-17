<template>
  <AppLayout @back="$inertia.visit(route('finance'))" :show-back-button="true">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <div class="flex items-center w-full space-x-2">
                <AtButton class="flex items-center h-10 space-x-2 text-primary" rounded @click="approveTransactionAll($event)">
                    <i class="block mr-2 fa fa-check"></i> Approve
                </AtButton>
                <AtButton class="flex items-center h-10 mr-2 space-x-2 text-primary" rounded @click="removeAllDrafts()">
                    <i class="block mr-2 fa fa-times"></i> Remove</AtButton>
                <AtButton class="flex items-center h-10 space-x-2 text-white bg-primary" rounded @click="runAutomations()">
                    <i class="block fa fa-robot"></i>
                </AtButton>
          </div>
        </template>
      </FinanceSectionNav>
    </template>
    <FinanceTemplate title="Transactions" :accounts="accounts">
      <Component
        :is="listComponent"
        :transactions="transactions.data"
        :server-search-options="serverSearchOptions"
        :with-teleport="true"
        @removed="removeTransaction"
        @approved="approveTransaction"
        @edit="handleEdit"
        :allow-mark-as-approved="true"
        :allow-remove="true"
      />
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup>
import { NSelect } from "naive-ui";
import { AtDatePager, AtButton } from "atmosphere-ui";
import { computed, reactive, watch } from "vue";

import AppLayout from "@/Layouts/AppLayout.vue";
import TransactionSearch from "@/Components/templates/TransactionSearch.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import TransactionTemplate from "@/Components/templates/TransactionTemplate.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";

import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
  transactions: {
    type: Array,
    default: () => [],
  },
  accounts: {
    type: Array,
    default: () => [],
  },
  serverSearchOptions: {
    type: Object,
    default: () => ({}),
  },
  accountId: {
    type: [Number, null],
  },
});


const isSelectedList = (listTypeName) => {
  return state.listType == listTypeName;
};

const listComponent = computed(() => {
  return props.accountId ? TransactionTemplate : TransactionSearch;
});


const runAutomations = () => {
    axios.post('/api/automation/run-all')
        .then(response => {
            console.log(response.data)
        })
        .catch(error => {
            console.log(error)
        })
}

const removeAllDrafts = () => {
    Inertia.post('/transactions/remove-all-drafts')
        .then(response => {
            Inertia.reload()
        })
}

const approveTransactionAll = (transaction) => {
    Inertia.post(`/transactions/approve-all-drafts`).then(() => {
        Inertia.reload();
    })
}

const approveTransaction = (transaction) => {
    Inertia.post(`/transactions/${transaction.id}/approve`).then(() => {
        Inertia.reload();
    })
}

const removeTransaction = (transaction) => {
    Inertia.delete(`/transactions/${transaction.id}`).then(() => {
        Inertia.reload();
    })
}
</script>

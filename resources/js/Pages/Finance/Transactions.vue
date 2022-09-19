<template>
  <AppLayout @back="$inertia.visit(route('finance'))" :show-back-button="true">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <div class="flex items-center w-full space-x-2">
            <AtDatePager
              class="w-full h-12 border-none bg-base-lvl-1 text-body"
              v-model:startDate="pageState.dates.startDate"
              v-model:endDate="pageState.dates.endDate"
              controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
              next-mode="month"
            />
            <FiltersPopover
                :options="{
                    filters: {
                        status: ['draft','verified']
                    },
                    relationships: ['linked']
                }"
                @onFilterChanged="pageState.filters=$event"
                @onRelationshipsChanged="pageState.relationships=$event"
            >
                <i class="block mr-2 fa fa-filter" />
            </FiltersPopover>
            <DraftButtons
                v-if="isDraft"
            />
          </div>
        </template>
      </FinanceSectionNav>
    </template>
    <FinanceTemplate title="Transactions" :accounts="accounts">
      <TransactionTemplate
        :transactions="transactions.data"
        :server-search-options="serverSearchOptions"
        @findLinked="findLinked"
        @removed="removeTransaction"
        @edit="handleEdit"
      />
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup>
import { NSelect } from "naive-ui";
import { AtDatePager, AtButton } from "atmosphere-ui";
import { computed, toRefs, provide} from "vue";
import { Inertia } from "@inertiajs/inertia";

import AppLayout from "@/Layouts/AppLayout.vue";
import TransactionSearch from "@/Components/templates/TransactionSearch.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import TransactionTemplate from "@/Components/templates/TransactionTemplate.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";

import { useTransactionModal } from "@/domains/transactions";
import { useServerSearch } from "./useServerSearch";
import DraftButtons from "./DraftButtons.vue";

const { openTransferModal } = useTransactionModal();

const props = defineProps({
  transactions: {
    type: Object,
    default: () => ({
        data: []
    }),
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
const {state: pageState, executeSearch } = useServerSearch(serverSearchOptions)

const selectedAccountId = computed(() => {
    return serverSearchOptions.value.filters?.account_id;
})

provide('selectedAccountId', selectedAccountId.value)

const isDraft = computed(() => {
    return serverSearchOptions.value.filters?.status == "draft";
});

const removeTransaction = (transaction) => {
    Inertia.delete(`/transactions/${transaction.id}`, {
        onSuccess() {
            Inertia.reload()
        }
    })
}

const findLinked = (transaction) => {
    Inertia.patch(`/transactions/${transaction.id}/linked`, {
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

<template>
  <AppLayout @back="$inertia.visit(route('finance'))" :show-back-button="true">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <div class="flex items-center w-full space-x-2">
            <DraftButtons
                v-if="isDraft"
            />
            <LogerButton variant="inverse">
                Add transaction
            </LogerButton>
            <StatusButtons
                v-model="currentStatus"
                :statuses="transactionStatus"
                @change="$inertia.visit($event)"
            />
            <AtDatePager
              class="w-full h-12 border-none bg-base-lvl-1 text-body"
              v-model:startDate="pageState.dates.startDate"
              v-model:endDate="pageState.dates.endDate"
              controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
              next-mode="month"
            />
          </div>
        </template>
      </FinanceSectionNav>
    </template>
    <FinanceTemplate title="Transactions" :accounts="accounts">
      <component
        :is="listComponent"
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
import { AtDatePager } from "atmosphere-ui";
import { computed, toRefs, provide, ref} from "vue";
import { Inertia } from "@inertiajs/inertia";
import { useBreakpoints, breakpointsTailwind } from "@vueuse/core";

import AppLayout from "@/Components/templates/AppLayout.vue";
import TransactionSearch from "@/Components/templates/TransactionSearch.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import TransactionTemplate from "@/Components/templates/TransactionTemplate.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import DraftButtons from "@/Components/DraftButtons.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";

import { useTransactionModal } from "@/domains/transactions";
import { useServerSearch } from "@/composables/useServerSearch";
import StatusButtons from "@/Components/molecules/StatusButtons.vue";

const { openTransactionModal } = useTransactionModal();

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
const {state: pageState } = useServerSearch(serverSearchOptions)
const selectedAccountId = computed(() => {
    return serverSearchOptions.value.filters?.account_id;
})

const { isSmaller } = useBreakpoints(breakpointsTailwind)
const listComponent = computed(() => {
    return isSmaller('md') ? TransactionSearch : TransactionTemplate;
});


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
    openTransactionModal({
        transactionData: transaction
    })
}


const transactionStatus = {
    verified: {
        label: 'Verified',
        value: '/finance/transactions?'
    },
    scheduled: {
        label: 'Scheduled',
        value: '/finance/transactions?filter[status]=scheduled'
    },
    draft: {
        label: 'Drafts',
        value: '/finance/transactions?filter[status]=draft&relationships=linked'
    },
}
const currentStatus = ref(serverSearchOptions.value.filters?.status || 'verified');
</script>

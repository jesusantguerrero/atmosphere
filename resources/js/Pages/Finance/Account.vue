<template>
  <AppLayout @back="$inertia.visit(route('finance'))" :show-back-button="true">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <div class="flex items-center w-full space-x-2">
            <LogerButton variant="inverse" class="" v-for="(item, statusName) in transactionStatus" :key="statusName" @click="$inertia.visit(item.value)">
                {{ item.label }}
            </LogerButton>
            <AtDatePager
              class="w-full h-12 border-none bg-base-lvl-1 text-body"
              v-model:startDate="pageState.dates.startDate"
              v-model:endDate="pageState.dates.endDate"
              controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
              next-mode="month"
            />
            <LogerButton  variant="inverse">
                Import Transactions
            </LogerButton>
            <DraftButtons
                v-if="isDraft"
            />
          </div>
        </template>
      </FinanceSectionNav>
    </template>
    <FinanceTemplate title="Transactions" :accounts="accounts">
      <TransactionTemplate
        :cols="tableAccountCols"
        :transactions="transactions"
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
import DraftButtons from "@/Components/DraftButtons.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import LogerDropdown from "@/Components/molecules/LogerDropdown.vue";

import { useTransactionModal } from "@/domains/transactions";
import { useServerSearch } from "@/composables/useServerSearch";
import { tableAccountCols } from '@/domains/transactions';

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


const transactionStatus = {
    draft: {
        label: 'Drafts',
        value: '/finance/transactions?filter[status]=draft&relationships=linked'
    },
    verified: {
        label: 'Verified',
        value: '/finance/transactions?'
    },
    scheduled: {
        label: 'Scheduled',
        value: '/finance/transactions?filter[status]=scheduled'
    }
}
</script>

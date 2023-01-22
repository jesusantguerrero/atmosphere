<template>
  <AppLayout :title="sectionTitle" @back="handleBackButton" :show-back-button="true">
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
                @change="$router.visit($event)"
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

    <FinanceTemplate title="Transactions" 
        :accounts="accounts" 
        :force-show-panel="!showTransactionTable"
       
    >
        <template #prepend-panel v-if="context.isMobile">
            <button v-ripple class="w-full py-3 font-bold text-body-1 bg-base-lvl-3 flex justify-between px-4 items-center" @click="showAllTransactions=true"> 
                All accounts 
                <IconBack class="transform rotate-180" />
            </button>
        </template>

        <component
            v-if="showTransactionTable"
            :is="listComponent"
            :transactions="transactions.data"
            :server-search-options="serverSearchOptions"
            all-accounts
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
import { router } from "@inertiajs/vue3";

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
import { useAppContextStore } from "@/store";
import IconBack from "@/Components/icons/IconBack.vue";

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

// mobile
const context = useAppContextStore()
const showAllTransactions = ref(false);
const showTransactionTable = computed(() => {
    return context.isMobile ? showAllTransactions.value : true;
})
const listComponent = computed(() => {
    return context.isMobile ? TransactionSearch : TransactionTemplate;
});
const sectionTitle = computed(() => {
    if (context.isMobile) {
        return showTransactionTable.value ? 'All transactions': "Accounts";
    }
    return "Transactions"
})

const handleBackButton = () => {
    if (context.isMobile && showTransactionTable.value) {
        showAllTransactions.value = false;
        return
    }
    return router.visit(route('finance'))
}

const { serverSearchOptions } = toRefs(props);
const {state: pageState } = useServerSearch(serverSearchOptions)
const selectedAccountId = computed(() => {
    return serverSearchOptions.value.filters?.account_id;
})


provide('selectedAccountId', selectedAccountId.value)

const isDraft = computed(() => {
    return serverSearchOptions.value.filters?.status == "draft";
});

const removeTransaction = (transaction) => {
    router.delete(`/transactions/${transaction.id}`, {
        onSuccess() {
            router.reload()
        }
    })
}

const findLinked = (transaction) => {
    router.patch(`/transactions/${transaction.id}/linked`, {
        onSuccess() {
            router.reload()
        }
    })
}

const { openTransactionModal } = useTransactionModal();
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

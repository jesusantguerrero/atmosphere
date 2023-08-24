<script setup lang="ts">
import { computed, toRefs, provide, ref, nextTick, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
// @ts-ignore
import { AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import AppSearch from "@/Components/AppSearch/AppSearch.vue";
import TransactionSearch from "@/Components/templates/TransactionSearch.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import TransactionTemplate from "@/Components/templates/TransactionTemplate.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import DraftButtons from "@/Components/DraftButtons.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";

import { useTransactionModal } from "@/domains/transactions";
import { useServerSearch, IServerSearchData } from "@/composables/useServerSearch";
import StatusButtons from "@/Components/molecules/StatusButtons.vue";
import { useAppContextStore } from "@/store";
import IconBack from "@/Components/icons/IconBack.vue";
import { IAccount, ITransaction } from "@/domains/transactions/models";
import { format } from "date-fns";
import axios from "axios";
import { reactive } from "vue";

const props = defineProps<{
  accounts: IAccount[],
  serverSearchOptions: IServerSearchData,
  accountId?: number,
}>();

// mobile
const context = useAppContextStore();
const showAllTransactions = ref(false);
const showTransactionTable = computed(() => {
  return context.isMobile ? showAllTransactions.value : true;
});
const listComponent = computed(() => {
  return context.isMobile ? TransactionSearch : TransactionTemplate;
});
const sectionTitle = computed(() => {
  if (context.isMobile) {
    return showTransactionTable.value ? "All transactions" : "Accounts";
  }
  return "Transactions";
});

const handleBackButton = () => {
  if (context.isMobile && showTransactionTable.value) {
    showAllTransactions.value = false;
    return;
  }
  return router.visit(route("finance"));
};

const { serverSearchOptions } = toRefs(props);
const {
     state: pageState,
     executeSearch,
     reset,
} = useServerSearch(serverSearchOptions, { manual: false }, async (urlParams) => {
    if (isLoading.value) return;
    const url = `/api/finance/transactions?${urlParams}`;
    console.time("fetch with axios");
    isLoading.value = true
    return axios.get(url).then((data) => {
        transactions.data = data.data
        isLoading.value = false
        console.timeEnd("fetch with axios")
    })
});

const isLoading = ref(false);
const transactions = reactive<{
    data: ITransaction[]
}>({
    data: []
})

const fetchTransactions = (params = location.toString()) => {
    // const url = `/api/${params}`;
    // return axios.get(url).then((data) => {
    //     transactions.data = data.data;
    //     console.log(data.data, " the data")
    //     isLoading.value = false
    // })
}
onMounted(() => {
    nextTick(() => {
        fetchTransactions()
    })
})

const selectedAccountId = computed(() => {
  return serverSearchOptions.value.filters?.account_id;
});

provide("selectedAccountId", selectedAccountId.value);

const isDraft = computed(() => {
  return serverSearchOptions.value.filters?.status == "draft";
});

const removeTransaction = (transaction: ITransaction) => {
  router.delete(`/transactions/${transaction.id}`, {
    onSuccess() {
      router.reload();
    },
  });
};

const findLinked = (transaction: ITransaction) => {
  router.patch(`/transactions/${transaction.id}/linked`, {
    onSuccess() {
      router.reload();
    },
  });
};

const { openTransactionModal } = useTransactionModal();
const handleEdit = (transaction: ITransaction) => {
  openTransactionModal({
    transactionData: transaction,
  });
};

const transactionStatus = {
  verified: {
    label: "Verified",
    value: "/finance/transactions?",
  },
  scheduled: {
    label: "Scheduled",
    value: "/finance/transactions?filter[status]=scheduled",
  },
  draft: {
    label: "Drafts",
    value: "/finance/transactions?filter[status]=draft&relationships=linked",
  },
};
const currentStatus = ref(serverSearchOptions.value.filters?.status || "verified");

const monthName = computed(() => format(pageState.dates.startDate, "MMMM"))

const listData = computed(() => {
    return transactions.data;
})
</script>


<template>
  <AppLayout :title="sectionTitle" @back="handleBackButton" :show-back-button="true">
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
            <DraftButtons v-if="isDraft" />
            <StatusButtons
              v-model="currentStatus"
              :statuses="transactionStatus"
              @change="router.visit($event)"
            />
            <LogerButton variant="secondary" :href="route('finance.export')"  target="_blank" as="a">
                <IMdiExport class="mr-2" />
                Export transactions
            </LogerButton>
          </div>
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate
      title="Transactions"
      :accounts="accounts"
      :force-show-panel="!showTransactionTable"
    >
      <template #prepend-panel v-if="context.isMobile">
        <button
          v-ripple
          class="flex items-center justify-between w-full px-4 py-3 font-bold text-body-1 bg-base-lvl-3"
          @click="showAllTransactions = true"
        >
          All accounts
          <IconBack class="transform rotate-180" />
        </button>
      </template>

      <main class="mt-4 bg-base-lvl-3">
        <header class="flex justify-between px-6 py-2">
            <section>
                <h4 class="text-lg font-bold text-body-1">
                    All transactions in <span class="text-secondary">
                        {{  monthName }}
                    </span>
                </h4>
                <AppSearch
                v-model.lazy="pageState.search"
                class="w-full md:flex"
                :has-filters="true"
                @clear="reset()"
                @blur="executeSearch"
              />
            </section>
        <span>
            {{  listData.length }}
        </span>
        </header>
            <component
              v-if="showTransactionTable"
              :is="listComponent"
              :transactions="listData"
              :server-search-options="serverSearchOptions"
              :is-loading="isLoading"
              all-accounts
              @findLinked="findLinked"
              @removed="removeTransaction"
              @edit="handleEdit"
            />
      </main>
    </FinanceTemplate>
  </AppLayout>
</template>


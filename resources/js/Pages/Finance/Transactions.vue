<script setup lang="ts">
import { computed, toRefs, reactive, provide, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { format } from "date-fns";
import axios from "axios";
// @ts-ignore
import { AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import AppSearch from "@/Components/AppSearch/AppSearch.vue";
import StatusButtons from "@/Components/molecules/StatusButtons.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import IconBack from "@/Components/icons/IconBack.vue";

import FinanceTemplate from "@/Pages/Finance/Partials/FinanceTemplate.vue";
import FinanceSectionNav from "@/Pages/Finance/Partials/FinanceSectionNav.vue";
import TransactionTable from "@/domains/transactions/components/TransactionTable.vue";
import TransactionSearch from "@/domains/transactions/components/TransactionSearch.vue";
import DraftButtons from "@/domains/transactions/components/DraftButtons.vue";

import { removeTransaction, useTransactionModal } from "@/domains/transactions";
import { useServerSearch, IServerSearchData } from "@/composables/useServerSearch";
import { useAppContextStore } from "@/store";
import { IAccount, ITransaction } from "@/domains/transactions/models";
import AccountFilter from "@/domains/transactions/components/AccountFilter.vue";

const { t } = useI18n();

const props = withDefaults(defineProps<{
  accounts: IAccount[],
  serverSearchOptions: Partial<IServerSearchData>,
  accountId?: number,
}>(), {
    serverSearchOptions: () => {
         return {}
    }
});

// mobile
const context = useAppContextStore();
const showAllTransactions = ref(false);
const showTransactionTable = computed(() => {
  return context.isMobile ? showAllTransactions.value : true;
});
const listComponent = computed(() => {
  return context.isMobile ? TransactionSearch : TransactionTable;
});
const sectionTitle = computed(() => {
  if (context.isMobile) {
    return showTransactionTable.value ? t("All transactions") : t("Accounts");
  }
  return t("Transactions");
});

const handleBackButton = () => {
  if (context.isMobile && showTransactionTable.value) {
    showAllTransactions.value = false;
    return;
  }
  return router.visit(route("finance"));
};

const isLoading = ref(false);
const { serverSearchOptions } = toRefs(props);
const {
     state: pageState,
     executeSearch,
     reset,
     hasFilters,
} = useServerSearch(serverSearchOptions, { manual: false, defaultDates: true }, async (urlParams) => {
    if (isLoading.value) return;
    const url = `/api/finance/transactions?${urlParams}`;
    isLoading.value = true
    window.history.pushState({}, null, `${location.pathname}?${urlParams}`)
    return axios.get(url).then((data) => {
        transactions.data = data.data
        isLoading.value = false
    })
});


const transactions = reactive<{
    data: ITransaction[]
}>({
    data: []
})

const fetchTransactions = (params = location.toString()) => {
    const url = `/api/${params}`;
    return axios.get(url).then((data) => {
        transactions.data = data.data;
        isLoading.value = false
    })
}

const selectedAccountId = computed(() => {
  return pageState?.filters?.account_id;
});

provide("selectedAccountId", selectedAccountId.value);

const isDraft = computed(() => {
  return pageState?.filters?.status == "draft";
});

const findLinked = (transaction: ITransaction) => {
  router.patch(`/transactions/${transaction.id}/linked`, {
    // @ts-ignore
    onSuccess() {
      router.reload();
    },
  });
};

const { openTransactionModal } = useTransactionModal();
const handleEdit = (transaction: ITransaction) => {
    axios.get(`/transactions/${transaction.id}?json=true`).then(({ data }) => {
        openTransactionModal({
          transactionData: data,
        });
    })
};

const handleDuplicate = (transaction: ITransaction) => {
    axios.get(`/transactions/${transaction.id}?json=true`).then(({ data }) => {
        delete data.id;
        openTransactionModal({
          transactionData: data,
        });
    })
};

const transactionStatus = {
  verified: {
    label: t("Verified"),
    value: "/finance/transactions?",
  },
  scheduled: {
    label: t("Scheduled"),
    value: "/finance/transactions?filter[status]=scheduled",
  },
  draft: {
    label: t("Drafts"),
    value: "/finance/transactions?filter[status]=draft&relationships=linked",
  },
};
const currentStatus = ref(pageState?.filters?.status || "verified");

watch(() => pageState?.filters, (filters) => {
    if (filters.status) {
        currentStatus.value = filters.status;
    }
}, { immediate: true })

const monthName = computed(() => format(pageState.dates.startDate, "MMMM"))

const listData = computed(() => {
    return transactions.data;
})

const goToAccount = (accountId: number) => {
    router.visit(`/finance/accounts/${accountId}`)
}

const buildExportUrl = (base: string): string => {
    const params = new URLSearchParams();
    const { startDate, endDate } = pageState.dates;
    if (startDate && endDate) {
        params.set('filter[date]', `${format(startDate, 'yyyy-MM-dd')}~${format(endDate, 'yyyy-MM-dd')}`);
    }
    if (pageState.filters?.account_id) {
        params.set('filter[account_id]', String(pageState.filters.account_id));
    }
    const query = params.toString();
    return query ? `${base}?${query}` : base;
};

const csvExportUrl = computed(() => buildExportUrl('/finance/transactions/export/csv'));
const pdfExportUrl = computed(() => buildExportUrl('/finance/transactions/export/pdf'));
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
            <DraftButtons v-if="isDraft" @submitted="fetchTransactions()" />
          </div>
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate
      :title="$t('Transactions')"
      :accounts="accounts"
      :hide-panel="!context.isMobile"
      :force-show-panel="context.isMobile && !showTransactionTable"
    >
      <template #prepend-panel v-if="context.isMobile" >
        <button
          v-ripple
          class="flex items-center justify-between w-full px-4 py-3 font-bold text-body-1 bg-base-lvl-3"
          @click="showAllTransactions = true"
        >
          {{ $t('All accounts') }}
          <IconBack class="transform rotate-180" />
        </button>
      </template>

      <main class="mt-4 ">
        <header class="flex bg-base-lvl-3 justify-between px-6 py-2">
            <section class="flex space-x-2 items-center">
                <StatusButtons
                    v-model="currentStatus"
                    :statuses="transactionStatus"
                    @change="router.visit($event)"
                />
                <AccountFilter
                    show-all
                    @update:model-value="goToAccount"
                />
            </section>

            <section class="flex items-center space-x-2">
                <AppSearch
                    v-model.lazy="pageState.search"
                    class="w-full md:flex"
                    :has-filters="hasFilters"
                    @clear="reset()"
                    @blur="executeSearch"
                />
                <span>
                    {{ listData.length }}
                </span>
                <a :href="csvExportUrl" target="_blank">
                    <LogerButton variant="inverse" as="span">
                        <IMdiDownload class="mr-1" />
                        CSV
                    </LogerButton>
                </a>
                <a :href="pdfExportUrl" target="_blank">
                    <LogerButton variant="inverse" as="span">
                        <IMdiFilePdfBox class="mr-1" />
                        PDF
                    </LogerButton>
                </a>
            </section>
        </header>

        <div
            v-if="!isLoading && listData.length === 0 && accounts.length === 0"
            class="mx-6 my-8 flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-base-lvl-2 px-6 py-12 text-center"
        >
            <div class="flex items-center justify-center w-14 h-14 rounded-full bg-primary/10 text-primary mb-4">
                <i class="fas fa-university text-2xl" />
            </div>
            <h3 class="text-lg font-bold text-body-1 mb-1">No accounts yet</h3>
            <p class="text-sm text-body-1/60 mb-5 max-w-xs">
                Start by adding your first account to track your transactions and balances.
            </p>
            <LogerButton
                variant="primary"
                :as="'a'"
                href="/finance/accounts/create"
            >
                <i class="fas fa-plus mr-2" />
                Add your first account
            </LogerButton>
        </div>

        <component
            v-else-if="showTransactionTable"
            :is="listComponent"
            :transactions="listData"
            :server-search-options="serverSearchOptions"
            :is-loading="isLoading"
            all-accounts
            @findLinked="findLinked"
            @removed="removeTransaction($event, ['verified'])"
            @duplicate="handleDuplicate"
            @edit="handleEdit"
        />
      </main>
    </FinanceTemplate>
  </AppLayout>
</template>


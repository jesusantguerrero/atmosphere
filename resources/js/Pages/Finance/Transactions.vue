<script setup lang="ts">
import { computed, toRefs, reactive, provide, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { addMonths, endOfMonth, format, isSameMonth, startOfMonth } from "date-fns";
import { formatMonth } from "@/utils";
import axios from "axios";
import { NDatePicker, NDropdown } from "naive-ui";

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
import { useImportModal } from "@/domains/transactions/useImportModal";
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
          mode: data.direction,
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

const monthName = computed(() => formatMonth(pageState.dates.startDate, "MMMM"))

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

// Month range so the Drafts view can clear just the visible month, not every draft.
const draftMonthStart = computed(() => pageState.dates.startDate ? format(pageState.dates.startDate, 'yyyy-MM-dd') : undefined);
const draftMonthEnd = computed(() => pageState.dates.endDate ? format(pageState.dates.endDate, 'yyyy-MM-dd') : undefined);

// Data actions live in the kebab — same slot they occupy on the register.
const { toggleModal: toggleImportModal } = useImportModal();

const exportOptions = [
    { key: 'import', label: 'Import' },
    { key: 'export-csv', label: 'Export CSV' },
    { key: 'export-pdf', label: 'Export PDF' },
];

const handleExport = (key: string) => {
    if (key === 'import') { toggleImportModal(); return; }
    if (key === 'export-csv') window.open(csvExportUrl.value, '_blank');
    if (key === 'export-pdf') window.open(pdfExportUrl.value, '_blank');
};

// Month pager — same control as the register: chevrons for neighbors, the
// picker for direct jumps, Today to come back. Replaces the old AtDatePager,
// which stepped one month at a time and didn't follow the dark theme.
const periodStart = (): Date => pageState.dates.startDate ? new Date(pageState.dates.startDate) : new Date();

const goToMonth = (date: Date) => {
    pageState.dates.startDate = startOfMonth(date);
    pageState.dates.endDate = endOfMonth(date);
};

const shiftMonth = (delta: number) => goToMonth(addMonths(periodStart(), delta));

const goToCurrentMonth = () => goToMonth(new Date());

const periodTimestamp = computed(() => periodStart().getTime());

const onMonthPicked = (timestamp: number | null) => {
    if (timestamp) {
        goToMonth(new Date(timestamp));
    }
};

const isCurrentMonth = computed(() => isSameMonth(periodStart(), new Date()));
</script>


<template>
  <AppLayout :title="sectionTitle" @back="handleBackButton" :show-back-button="true">
    <template #header>
      <FinanceSectionNav />
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

      <main class="mt-4">
        <!-- Unified toolbar grammar (same as the register):
             search → segmented filter → contextual filters … period → data
             actions. One row; the floating count moved to the card footer. -->
        <header class="flex flex-col md:flex-row md:items-center bg-base-lvl-3 gap-3 md:gap-2 px-4 md:px-6 py-3 md:py-2">
            <AppSearch
                v-model.lazy="pageState.search"
                class="w-full md:max-w-xs"
                :has-filters="hasFilters"
                @clear="reset()"
                :placeholder="$t('Search')"
                @blur="executeSearch"
            />
            <StatusButtons
                v-model="currentStatus"
                :statuses="transactionStatus"
                @change="router.visit($event)"
            />
            <AccountFilter
                show-all
                @update:model-value="goToAccount"
            />
            <DraftButtons v-if="isDraft" :start="draftMonthStart" :end="draftMonthEnd" @submitted="fetchTransactions()" />

            <div class="flex items-center gap-1 md:ml-auto shrink-0">
                <button
                    type="button"
                    class="px-2 py-1 rounded text-body-1 hover:bg-base-lvl-2"
                    :title="$t('Previous month')"
                    @click="shiftMonth(-1)"
                >
                    <IMdiChevronLeft />
                </button>

                <NDatePicker
                    type="month"
                    format="MMM yyyy"
                    size="small"
                    class="w-32"
                    :value="periodTimestamp"
                    :clearable="false"
                    @update:value="onMonthPicked"
                />

                <button
                    type="button"
                    class="px-2 py-1 rounded text-body-1 hover:bg-base-lvl-2"
                    :title="$t('Next month')"
                    @click="shiftMonth(1)"
                >
                    <IMdiChevronRight />
                </button>

                <button
                    v-if="!isCurrentMonth"
                    type="button"
                    class="px-2 py-1 text-xs font-semibold rounded text-primary hover:bg-base-lvl-2"
                    :title="$t('Back to current month')"
                    @click="goToCurrentMonth()"
                >
                    {{ $t('Today') }}
                </button>

                <NDropdown trigger="click" key-field="key" :options="exportOptions" @select="handleExport">
                    <button type="button" class="px-2 py-1.5 rounded text-body-1 hover:bg-base-lvl-2" :title="$t('More actions')">
                        <IMdiDotsVertical />
                    </button>
                </NDropdown>
            </div>
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
        >
            <template #empty>
                <div class="mx-6 my-8 flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-base-lvl-2 px-6 py-12 text-center">
                    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-primary/10 text-primary mb-4">
                        <i class="fas fa-receipt text-2xl" />
                    </div>
                    <h3 class="text-lg font-bold text-body-1 mb-1">
                        {{ hasFilters ? 'No matching transactions' : `No transactions in ${monthName}` }}
                    </h3>
                    <p class="text-sm text-body-1/60 mb-5 max-w-xs">
                        {{ hasFilters
                            ? 'Try adjusting your search or filters to find what you\'re looking for.'
                            : 'Transactions for this period will show up here as they come in.' }}
                    </p>
                    <LogerButton v-if="hasFilters" variant="inverse" @click="reset()">
                        <i class="fas fa-times mr-2" />
                        Clear filters
                    </LogerButton>
                </div>
            </template>
        </component>

        <footer
            v-if="showTransactionTable && listData.length"
            class="flex items-center justify-end px-5 py-2.5 text-xs font-semibold border-t text-body-1/60 border-base bg-base-lvl-3"
        >
            {{ listData.length }}
            {{ listData.length === 1 ? $t('Transaction') : $t('Transactions') }}
        </footer>
      </main>
    </FinanceTemplate>
  </AppLayout>
</template>


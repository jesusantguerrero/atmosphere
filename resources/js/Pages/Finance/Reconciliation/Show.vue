<script setup lang="ts">
import { computed, toRefs, provide, ref, onMounted, watch } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { AtBackgroundIconCard, AtField } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import AppSearch from "@/Components/AppSearch/AppSearch.vue";

import LogerButton from "@/Components/atoms/LogerButton.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";

import FinanceTemplate from "../Partials/FinanceTemplate.vue";
import FinanceSectionNav from "../Partials/FinanceSectionNav.vue";
import ReconciliationTable from "@/domains/transactions/components/ReconciliationTable.vue";

import { useTransactionModal } from "@/domains/transactions";
import { IServerSearchData, useServerSearch } from "@/composables/useServerSearchV2";
import { tableAccountCols } from "@/domains/transactions";
import { formatMoney } from "@/utils";
import { IAccount, ICategory, ITransaction } from "@/domains/transactions/models";
import { NPagination } from "naive-ui";
import axios from "axios";

const { openTransactionModal } = useTransactionModal();

interface CollectionData<T> {
  data: T[];
}
const props = withDefaults(
  defineProps<{
    transactions: ITransaction[];
    matchedCount?: number;
    totalEntries?: number;
    ledgerBalance?: number;
    stats: CollectionData<Record<string, number>>;
    account: IAccount;
    accounts: IAccount[];
    reconciliation: Record<string, any>;
    categories: ICategory[];
    serverSearchOptions: Partial<IServerSearchData>;
    accountId?: number;
  }>(),
  {
    serverSearchOptions: () => {
      return {};
    },
  }
);

const isLoading = ref(false);
const { serverSearchOptions, accountId, accounts } = toRefs(props);

provide("selectedAccountId", accountId);

const { state } = useServerSearch(serverSearchOptions)


interface ReconciliationEntry {
    entry_id: number
    transaction_id: number
    is_matched: boolean
}

// Destructive flow split into two stages so a single dropdown click
// can never delete a transaction. Click 'Delete transaction…' opens
// the confirm modal — only its confirm button actually fires the
// DELETE. 'Unmatch' (the common case) just clears the match flag.

const pendingDelete = ref<ReconciliationEntry | null>(null);

const requestRemoveTransaction = (transaction: ReconciliationEntry) => {
  pendingDelete.value = transaction;
};

const cancelRemoveTransaction = () => {
  pendingDelete.value = null;
};

const confirmRemoveTransaction = () => {
  if (!pendingDelete.value) return;
  const tx = pendingDelete.value;
  router.delete(`/transactions/${tx.transaction_id}`, {
    onSuccess() {
      router.reload();
    },
    onFinish() {
      pendingDelete.value = null;
    },
  });
};

// 'Unmatch' is the safe default action — just clears the match flag,
// no deletion. Reuses the same endpoint as toggleCheck so the
// reconciliation totals refresh consistently.
const unmatchTransaction = (entry: ReconciliationEntry) => {
  router.put(
    `/finance/reconciliation/${props.reconciliation.id}/reconciliation-entries/${entry.entry_id}/check`,
    { matched: false },
    {
      preserveScroll: true,
      preserveState: true,
      only: ['transactions', 'matchedCount', 'totalEntries'],
      onSuccess() {
        router.reload();
      },
    }
  );
};

// ─── Bulk selection ──────────────────────────────────────────
// Common case: 'I see 8 obvious matches, mark them all in one go'.
// Without this the user has to click 8 individual rows; with 50+
// transaction statements that's tedious enough to push users away
// from regular reconciliation.
const selectedRows = ref<any[]>([]);
const reconciliationTableRef = ref<any>(null);
const bulkProcessing = ref(false);

const onSelectionChange = (rows: any[]) => {
  selectedRows.value = Array.isArray(rows) ? rows : [];
};

const bulkSetMatched = async (matched: boolean) => {
  if (!selectedRows.value.length || bulkProcessing.value) return;
  bulkProcessing.value = true;
  try {
    // Fire all PUTs in parallel — the per-row endpoint is idempotent
    // and the backend recomputes totals on each, so we just need to
    // reload once at the end.
    await Promise.all(
      selectedRows.value
        .filter((row) => row.entry_id)
        .map((row) =>
          axios.put(
            `/finance/reconciliation/${props.reconciliation.id}/reconciliation-entries/${row.entry_id}/check`,
            { matched }
          )
        )
    );
    reconciliationTableRef.value?.clearSelection?.();
    selectedRows.value = [];
    router.reload({ only: ['transactions', 'matchedCount', 'totalEntries'] });
  } finally {
    bulkProcessing.value = false;
  }
};

const bulkClear = () => {
  reconciliationTableRef.value?.clearSelection?.();
  selectedRows.value = [];
};



const toggleCheck = (entry: ReconciliationEntry) => {
  router.put(`/finance/reconciliation/${props.reconciliation.id}/reconciliation-entries/${entry.entry_id}/check`, {
    matched: !Boolean(entry.is_matched),
  }, {
    preserveScroll: true,
    preserveState: true,
    only: ['transactions', 'matchedCount', 'totalEntries'],
    onSuccess() {
      router.reload();
    },
  });
};

const findLinked = (transaction: ITransaction) => {
  router.patch(`/transactions/${transaction.id}/linked`, {
    // @ts-ignore
    onSuccess() {
      router.reload();
    },
  });
};

const handleEdit = (transaction: ITransaction) => {
    axios.get(`/transactions/${transaction.transaction_id}?json=true`).then(({ data }) => {
        openTransactionModal({
            transactionData: data,
        });
    })
};

// reconciliation


// Statement balance is now always editable — the pencil-toggle
// pattern was friction-without-value. The user came here to enter
// this number; making them click an icon first to enable the input
// was reverse onboarding.
const reconcileForm = useForm({
  date: props.reconciliation.date,
  balance: props.reconciliation.amount,
});

const completeReconciliation = () => {
    if (reconcileForm.processing) return
  reconcileForm
    .transform((data) => ({
      ...data,
      date: props.reconciliation.date,
    }))
    .put(`/finance/reconciliation/${props.reconciliation.id}`, {
      onFinish() {
        reconcileForm.reset();
        reconcileForm.isVisible = false;
      },
    });
};

const syncReconciliationForm = useForm({});
const syncReconciliation = async () => {
    if (syncReconciliationForm.processing) return
    syncReconciliationForm
        .put(`/finance/reconciliation/${props.reconciliation.id}/sync-transactions`, {
        only: ['transactions', 'matchedCount', 'totalEntries'],
            preserveScroll: true,
            preserveState: true,
        });
};

// Two-stage destructive flow — same pattern as transaction delete.
// Was previously using the native browser confirm() dialog which is
// jarring and inconsistent with the rest of the app's modal styling.
const showDeleteReconciliationModal = ref(false);

const requestDeleteReconciliation = () => {
  showDeleteReconciliationModal.value = true;
};

const cancelDeleteReconciliation = () => {
  showDeleteReconciliationModal.value = false;
};

const confirmDeleteReconciliation = () => {
  router.delete(`/finance/reconciliation/${props.reconciliation.id}`, {
    only: ['transactions', 'matchedCount', 'totalEntries'],
    preserveScroll: true,
    preserveState: true,
    onSuccess() {
      router.visit(`/finance/accounts/${props.reconciliation.account_id}`);
    },
    onFinish() {
      showDeleteReconciliationModal.value = false;
    },
  });
};
onMounted(() => {
  router.on("start", () => (isLoading.value = true));
  router.on("finish", () => (isLoading.value = false));
});

const transactionList = computed(() => {
  return props.transactions.data;
});

// Matched count comes from the server so it spans ALL pages — the paginated
// .data only holds the current 25 rows, and counting those made the header
// progress lie whenever there was more than one page.
const transactionsMatched = computed(() => {
  return props.matchedCount ?? props.transactions.data.filter(item => item.matched).length;
});

// Unfiltered total for progress. transactions.total shrinks when the
// pending/matched filter is on, which would corrupt "x / total matched".
const totalTransactions = computed(() => {
  return props.totalEntries ?? props.transactions.total ?? 0;
});

// ─── Match status filter (All / Pending / Matched) ───────────
// Server-side (?matched=pending|matched) so it spans every page, not just
// the 25 loaded rows. With the pending filter on, checking a row makes it
// leave the list on reload — the remaining work is always what's on screen.
const matchedFilter = ref<string>(new URLSearchParams(window.location.search).get('matched') ?? '');
const searchQuery = ref<string>(new URLSearchParams(window.location.search).get('search') ?? '');

const filterOptions = computed(() => [
  { value: '', label: 'All', count: totalTransactions.value },
  { value: 'pending', label: 'Pending', count: totalTransactions.value - transactionsMatched.value },
  { value: 'matched', label: 'Matched', count: transactionsMatched.value },
]);

const buildListParams = (extra: Record<string, any> = {}) => {
  const params: Record<string, any> = { ...extra };
  if (matchedFilter.value) params.matched = matchedFilter.value;
  if (searchQuery.value) params.search = searchQuery.value;
  return params;
};

const reloadList = (params: Record<string, any>) => {
  router.get(window.location.pathname, params, {
    preserveState: true,
    preserveScroll: true,
    only: ['transactions', 'matchedCount', 'totalEntries'],
  });
};

const setMatchedFilter = (value: string) => {
  if (matchedFilter.value === value) return;
  matchedFilter.value = value;
  reloadList(buildListParams({ page: 1 }));
};

// Search by amount or payee — the number on the bank statement is what the
// user cross-checks against, so a debounced server-side lookup beats
// scanning 75 rows by eye.
let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, () => {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => reloadList(buildListParams({ page: 1 })), 350);
});

// Server-side pagination (25 per page). Partial visit keeps scroll and local
// state; only the table data and counters travel.
const goToPage = (page: number) => {
  reloadList(buildListParams({ page }));
};

// Ledger balance AS OF the reconciliation date. account.balance is the
// current balance — on past reconciliations it produced a nonsense
// difference against that month's statement.
const ledgerBalance = computed(() => {
  return props.ledgerBalance ?? props.account.balance ?? 0;
});

// Sprint 1 derived state — progress / difference / status
//
// `progressPercent`  → 0-100, drives the progress bar width and color
// `progressBarColor` → emerald when fully matched, primary while in-flight
// `difference`       → Loger balance − statement balance; the signed gap
//                      between what the bank says and what Loger thinks.
//                      Reconciliation is "done" when |difference| < 1 cent.
// `differenceColor`  → green when matched, red otherwise — same color
//                      semantic the user expects from any "fix this to zero"
//                      surface (credit-card utilization, budget remaining).
// `differenceDirection` → human label "Loger higher" / "Statement higher"
//                         so the user knows which side to investigate.
const progressPercent = computed(() => {
  const total = totalTransactions.value;
  if (!total) return 0;
  return Math.round((transactionsMatched.value / total) * 100);
});

const progressBarColor = computed(() => {
  return progressPercent.value === 100 ? 'bg-emerald-500' : 'bg-primary';
});

const difference = computed(() => {
  const stmt = Number(reconcileForm.balance) || 0;
  return (ledgerBalance.value ?? 0) - stmt;
});

const isMatched = computed(() => Math.abs(difference.value) < 0.01);

const differenceColor = computed(() => {
  return isMatched.value ? 'text-emerald-600' : 'text-error';
});

const differenceLabel = computed(() => {
  return isMatched.value ? 'Matched' : 'Difference';
});

const differenceDirection = computed(() => {
  if (isMatched.value) return '';
  return difference.value > 0 ? 'Loger higher' : 'Statement higher';
});
</script>

<template>
  <AppLayout
    @back="router.visit('/finance/transactions')"
    :title="account.name"
    :show-back-button="true"
  >
    <template #header>
      <FinanceSectionNav />
    </template>

    <template #title>
    <section class="flex items-center flex-wrap gap-2">
        <h1 class="font-bold">
            <span class="text-body-1/60">Reconciliation of </span>
            <span>{{ account.name }}</span>
        </h1>
        <!-- Status badge so the user immediately knows whether this
             reconciliation is still open or already locked in. -->
        <span
          v-if="reconciliation.status === 'completed'"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold uppercase tracking-wide bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200"
        >
          <IMdiCheckCircle class="w-3 h-3" />
          {{ $t('Completed') }}
        </span>
        <span
          v-else
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold uppercase tracking-wide bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200"
        >
          <IMdiClockOutline class="w-3 h-3" />
          {{ $t('Pending') }}
        </span>
        <span v-if="reconciliation.date" class="text-xs text-body-1/60 ml-1">
          · {{ reconciliation.date }}
        </span>
        <Link :href="`/finance/accounts/${account.id}/reconciliations/`"
            title="reconciliations"
            class="inline-block ml-2 font-bold text-secondary"
        >
            <IMdiHistory />
        </Link>
    </section>
  </template>

    <FinanceTemplate :title="$t('Transactions')" :accounts="accounts">
      <!-- Stats row hidden when empty — was rendering an empty bar
           because the controller doesn't populate it today. -->
      <div v-if="stats && Object.keys(stats).length" class="flex mt-4 space-x-4">
        <AtBackgroundIconCard
          class="w-full cursor-pointer text-body-1 bg-base-lvl-3"
          v-for="stat in stats"
          :value="formatMoney(stat)"
        />
      </div>

      <section class="bg-base-lvl-3 mt-4 rounded-t-lg overflow-hidden">
        <!-- Sticky wrapper: context numbers + the 2px progress line stay
             pinned while the table scrolls. The old full-width MATCH PROGRESS
             band was redundant chrome — the Pending/Matched counts and this
             hairline carry the same information. -->
        <div class="md:sticky md:top-0 md:z-20 bg-base-lvl-3">
        <header class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 md:gap-6 px-4 md:px-6 py-3">
          <div class="flex flex-col sm:flex-row sm:items-end gap-3 sm:gap-6 flex-wrap">
            <AtField :label="$t('Statement balance')">
              <LogerInput
                class="opacity-100 cursor-text"
                v-model="reconcileForm.balance"
                :number-format="true"
              >
                <template #prefix>
                  {{ account.currency_code }}
                </template>
              </LogerInput>
            </AtField>

            <AtField :label="$t('Loger balance')">
              <div>
                <span class="tabular-nums">{{ formatMoney(ledgerBalance, account.currency_code) }}</span>
                <p v-if="reconciliation.date" class="text-[10px] text-body-1/50 leading-tight">
                  {{ $t('as of') }} {{ reconciliation.date }}
                </p>
              </div>
            </AtField>

            <!-- Difference: red when ≠ 0, green when matched — the number
                 being driven to zero. Direction + match count live in its
                 sublabel: it's where the eye already is when asking
                 "am I done?". -->
            <AtField :label="differenceLabel">
              <div>
                <div class="flex items-baseline gap-1">
                  <span class="font-bold tabular-nums text-lg" :class="differenceColor">
                    {{ formatMoney(Math.abs(difference)) }}
                  </span>
                  <IMdiCheckCircle v-if="isMatched" class="w-4 h-4 text-emerald-500 ml-1" />
                </div>
                <p class="text-[10px] text-body-1/50 leading-tight">
                  <template v-if="differenceDirection">{{ differenceDirection.toLowerCase() }} · </template>{{ transactionsMatched }}/{{ totalTransactions }} {{ $t('matched') }}
                </p>
              </div>
            </AtField>
          </div>

          <!-- Action hierarchy:
                 primary  = Complete (inverse / accent)
                 secondary = Pull new (neutral)
                 destructive = Delete (icon-only, separated by divider) -->
          <div class="flex items-center gap-2">
            <LogerButton
              variant="inverse"
              v-if="reconciliation.status != 'completed'"
              @click="completeReconciliation()"
              :processing="reconcileForm.processing"
              :disabled="reconcileForm.balance === null || reconcileForm.balance === undefined || reconcileForm.balance === ''"
            >
              <IMdiCheck class="mr-1" />
              {{ $t('Complete') }}
            </LogerButton>
            <LogerButton
              variant="neutral"
              v-if="reconciliation.status != 'completed'"
              @click="syncReconciliation()"
              :processing="syncReconciliationForm.processing"
              :title="$t('Pull new transactions from the account')"
            >
              <IMdiSync class="mr-1" :class="{'animate-spin': syncReconciliationForm.processing}" />
              {{ $t('Pull new') }}
            </LogerButton>
            <div
              v-if="reconciliation.status != 'completed'"
              class="w-px h-6 bg-base mx-1"
            />
            <button
              v-if="reconciliation.status != 'completed'"
              type="button"
              @click="requestDeleteReconciliation()"
              :disabled="syncReconciliationForm.processing || reconcileForm.processing"
              class="p-2 rounded-md text-body-1/40 hover:text-error hover:bg-error/5 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-error/40 disabled:opacity-50"
              :title="$t('Delete this reconciliation')"
            >
              <IMdiTrash class="w-4 h-4" />
            </button>
          </div>
        </header>

        <!-- Hairline progress: fills toward 100% as rows are matched. -->
        <div class="h-0.5 bg-base" :title="progressPercent + '% matched'">
          <div
            class="h-full transition-all duration-500"
            :class="progressBarColor"
            :style="{ width: progressPercent + '%' }"
          />
        </div>
        </div>

        <!-- List controls: search + match status filter. Same grammar as the
             register toolbar — search first, segmented filter next. The filter
             is server-side so it spans every page; with 'Pending' on, checked
             rows leave the list and what's on screen is the remaining work. -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 px-4 md:px-6 py-2 border-b border-base">
          <AppSearch
            v-model.lazy="searchQuery"
            class="w-full sm:max-w-[220px]"
            :placeholder="$t('Amount or payee')"
            :has-filters="Boolean(searchQuery)"
            @clear="searchQuery = ''"
          />
          <div class="inline-flex self-start p-0.5 text-xs rounded-lg bg-base-lvl-1 sm:self-auto">
            <button
              v-for="option in filterOptions"
              :key="option.value"
              type="button"
              class="px-3 py-1.5 rounded-md transition-colors"
              :class="matchedFilter === option.value
                ? 'bg-base-lvl-3 text-body font-semibold shadow-sm'
                : 'text-body-1/60 hover:text-body'"
              @click="setMatchedFilter(option.value)"
            >
              {{ $t(option.label) }} <span class="opacity-60">({{ option.count }})</span>
            </button>
          </div>
        </div>

        <!-- Bulk action bar — only renders when rows are selected.
             Lets the user mark/unmark many rows at once instead of
             one-by-one for long statements. -->
        <Transition
          enter-active-class="transition duration-150 ease-out"
          enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-100 ease-in"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="selectedRows.length"
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-4 md:px-6 py-2 bg-primary/10 border-y border-primary/20"
          >
            <p class="text-sm font-medium text-body">
              {{ selectedRows.length }} {{ $t('selected') }}
            </p>
            <div class="flex items-center gap-2 flex-wrap">
              <LogerButton
                variant="inverse"
                :disabled="bulkProcessing"
                :processing="bulkProcessing"
                @click="bulkSetMatched(true)"
              >
                <IMdiCheck class="mr-1" />
                {{ $t('Mark matched') }}
              </LogerButton>
              <LogerButton
                variant="neutral"
                :disabled="bulkProcessing"
                @click="bulkSetMatched(false)"
              >
                <IMdiClose class="mr-1" />
                {{ $t('Unmark') }}
              </LogerButton>
              <button
                type="button"
                class="px-2 py-1 text-xs text-body-1/60 hover:text-body transition"
                @click="bulkClear"
              >
                {{ $t('Clear selection') }}
              </button>
            </div>
          </div>
        </Transition>

        <ReconciliationTable
          ref="reconciliationTableRef"
          :cols="tableAccountCols(props.reconciliation.account_id)"
          :transactions="transactionList"
          :server-search-options="serverSearchOptions"
          :is-loading="isLoading"
          @toggleCheck="toggleCheck"
          @findLinked="findLinked"
          @unmatched="unmatchTransaction"
          @removed="requestRemoveTransaction"
          @edit="handleEdit"
          @selection-change="onSelectionChange"
        >
            <template #footer v-if="transactions.last_page > 1">
                <footer class="flex items-center justify-between px-4 mt-4">
                    <span class="text-xs text-body-1/60">
                        {{ transactions.from }}–{{ transactions.to }} {{ $t('of') }} {{ transactions.total }}
                    </span>
                    <NPagination
                        :page="transactions.current_page"
                        :page-count="transactions.last_page"
                        @update:page="goToPage"
                    />
                </footer>
            </template>
        </ReconciliationTable>

      </section>
    </FinanceTemplate>

    <!-- Confirm modal for deleting the entire reconciliation. -->
    <ConfirmationModal
      :show="showDeleteReconciliationModal"
      @close="cancelDeleteReconciliation"
    >
      <template #title>{{ $t('Delete reconciliation?') }}</template>
      <template #content>
        <p class="text-sm text-body-1">
          {{ $t('This will permanently delete this reconciliation and unmatch all its transactions. The transactions themselves stay in your account.') }}
        </p>
      </template>
      <template #footer>
        <LogerButton variant="neutral" @click="cancelDeleteReconciliation">
          {{ $t('Cancel') }}
        </LogerButton>
        <LogerButton
          variant="error"
          class="ml-2"
          @click="confirmDeleteReconciliation"
        >
          {{ $t('Delete reconciliation') }}
        </LogerButton>
      </template>
    </ConfirmationModal>

    <!-- Confirm modal for permanently deleting a transaction from the
         account. Separate from unmatch — unmatch is one-click safe. -->
    <ConfirmationModal
      :show="pendingDelete !== null"
      @close="cancelRemoveTransaction"
    >
      <template #title>{{ $t('Delete transaction?') }}</template>
      <template #content>
        <p class="text-sm text-body-1">
          {{ $t('This will permanently delete the transaction from your account. To only remove it from this reconciliation, use Unmatch instead.') }}
        </p>
      </template>
      <template #footer>
        <LogerButton variant="neutral" @click="cancelRemoveTransaction">
          {{ $t('Cancel') }}
        </LogerButton>
        <LogerButton
          variant="error"
          class="ml-2"
          @click="confirmRemoveTransaction"
        >
          {{ $t('Delete transaction') }}
        </LogerButton>
      </template>
    </ConfirmationModal>
  </AppLayout>
</template>

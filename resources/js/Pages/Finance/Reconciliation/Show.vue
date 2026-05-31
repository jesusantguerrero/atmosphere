<script setup lang="ts">
import { computed, toRefs, provide, ref, onMounted, nextTick } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { AtBackgroundIconCard, AtField } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";

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
      only: ['transactions'],
      onSuccess() {
        router.reload();
      },
    }
  );
};



const toggleCheck = (entry: ReconciliationEntry) => {
  router.put(`/finance/reconciliation/${props.reconciliation.id}/reconciliation-entries/${entry.entry_id}/check`, {
    matched: !Boolean(entry.is_matched),
  }, {
    preserveScroll: true,
    preserveState: true,
    only: ['transactions'],
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


const isEditing = ref(false);
const statementBalanceRef = ref();
const toggleEditing = () => {
  isEditing.value = !isEditing.value;
  if (isEditing.value) {
    nextTick(() => {
      statementBalanceRef.value.focus();
    });
  }
};

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
        only: ['transactions'],
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
    only: ['transactions'],
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

const transactionsMatched = computed(() => {
  return props.transactions.data.filter(item => item.matched).length;
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
  const total = props.transactions.total || 0;
  if (!total) return 0;
  return Math.round((transactionsMatched.value / total) * 100);
});

const progressBarColor = computed(() => {
  return progressPercent.value === 100 ? 'bg-emerald-500' : 'bg-primary';
});

const progressTextColor = computed(() => {
  if (progressPercent.value === 100) return 'text-emerald-600';
  if (progressPercent.value > 0) return 'text-primary';
  return 'text-body-1/40';
});

const difference = computed(() => {
  const stmt = Number(reconcileForm.balance) || 0;
  return (props.account.balance ?? 0) - stmt;
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
        <!-- Progress hero. Previously buried as a 12px label next to the
             other AtFields; this is the single number that answers
             "am I done?" so it gets size + a bar. -->
        <div class="px-6 pt-4 pb-3 border-b border-base">
          <div class="flex items-center justify-between gap-4 flex-wrap">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
              {{ $t('Match progress') }}
            </h2>
            <p class="tabular-nums">
              <span class="text-2xl font-bold" :class="progressTextColor">
                {{ transactionsMatched }}
              </span>
              <span class="text-body-1/60 text-sm"> / {{ transactions.total }} {{ $t('matched') }}</span>
              <span class="ml-2 text-xs text-body-1/50">({{ progressPercent }}%)</span>
            </p>
          </div>
          <div class="mt-2 w-full h-1.5 rounded-full bg-base overflow-hidden">
            <div
              class="h-full rounded-full transition-all duration-500"
              :class="progressBarColor"
              :style="{ width: progressPercent + '%' }"
            />
          </div>
        </div>

        <header class="flex items-end justify-between gap-6 px-6 py-3 flex-wrap">
          <div class="flex items-end gap-6 flex-wrap">
            <AtField :label="$t('Statement balance')">
              <LogerInput
                ref="statementBalanceRef"
                class="opacity-100 cursor-text"
                v-model="reconcileForm.balance"
                :number-format="true"
                :disabled="!isEditing"
                @blur="isEditing = false"
              >
                <template #prefix>
                  {{ account.currency_code }}
                </template>
                <template #suffix>
                  <IMdiPencil class="cursor-pointer" @click.prevent="toggleEditing" />
                </template>
              </LogerInput>
            </AtField>

            <AtField :label="$t('Loger balance')">
              <span class="tabular-nums">{{ formatMoney(account.balance, account.currency_code) }}</span>
            </AtField>

            <!-- Difference: red when ≠ 0, green when matched. This is the
                 number the user is trying to drive to zero, so it now
                 carries the most visual weight and a directional label. -->
            <AtField :label="differenceLabel">
              <div class="flex items-baseline gap-1">
                <span class="font-bold tabular-nums text-lg" :class="differenceColor">
                  {{ formatMoney(Math.abs(difference)) }}
                </span>
                <span v-if="differenceDirection" class="text-[10px] uppercase tracking-wide text-body-1/60">
                  {{ differenceDirection }}
                </span>
                <IMdiCheckCircle v-if="isMatched" class="w-4 h-4 text-emerald-500 ml-1" />
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

        <ReconciliationTable
          :cols="tableAccountCols(props.reconciliation.account_id)"
          :transactions="transactionList"
          :server-search-options="serverSearchOptions"
          :is-loading="isLoading"
          @toggleCheck="toggleCheck"
          @findLinked="findLinked"
          @unmatched="unmatchTransaction"
          @removed="requestRemoveTransaction"
          @edit="handleEdit"
        >
            <template #footer v-if="false">
                <footer class="justify-end flex px-4 mt-4">
                    <NPagination v-model:page="state.page" :page-count="Math.ceil(transactions.total / 25)" />
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

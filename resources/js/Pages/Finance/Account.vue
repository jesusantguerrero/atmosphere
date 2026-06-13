<script setup lang="ts">
import { computed, toRefs, provide, reactive, ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { format } from "date-fns";
import { AtDatePager } from "atmosphere-ui";
import axios from "axios";

import { useServerSearch, IServerSearchData } from "@/composables/useServerSearchV2";

import AppLayout from "@/Components/templates/AppLayout.vue";
import AppSearch from "@/Components/AppSearch/AppSearch.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";

import FinanceTemplate from "./Partials/FinanceTemplate.vue";
import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";
import AccountReconciliationBanner from "./Partials/AccountReconciliationBanner.vue";
import TransactionSearch from "@/domains/transactions/components/TransactionSearch.vue";
import TransactionTable from "@/domains/transactions/components/TransactionTable.vue";
import MultiCurrencyDetailPanel from "@/domains/transactions/components/MultiCurrencyDetailPanel.vue";
import AccountReconciliationForm from "./AccountReconciliationForm.vue";

import { NDropdown } from "naive-ui";

import { useTransactionModal, TRANSACTION_DIRECTIONS, removeTransaction } from "@/domains/transactions";
import { tableAccountCols } from "@/domains/transactions";
import { paymentMethods } from "@/domains/transactions/constants";
import { useAppContextStore } from "@/store";
import { formatDate, formatMoney } from "@/utils";
import { IAccount, ICategory, ITransaction } from "@/domains/transactions/models";
import NextPaymentsWidget from "@/domains/transactions/components/NextPaymentsWidget.vue";
import { usePaymentModal } from "@/domains/transactions/usePaymentModal";
import WidgetContainer from "@/Components/WidgetContainer.vue";
import Modal from "@/Components/atoms/Modal.vue";
import ImportHolder from "@/Components/organisms/ImportHolder.vue";

const { openTransactionModal } = useTransactionModal();
const { openModal } = usePaymentModal();
const { t } = useI18n();


const props = withDefaults(defineProps<{
    accountDetailTypes: { label: string, id: number | string }[];
    transactions: ITransaction[];
    drafts?: ITransaction[];
    billingCycles: ITransaction[];
    lastCreditCardPayment?: { date: string, amount: number, source_account: string | null, is_linked: boolean } | null;
    stats: { total: number, credit: number, debit: number };
    accounts: IAccount[];
    categories: ICategory[],
    serverSearchOptions: Partial<IServerSearchData>,
    accountId?: number,
    startingBalance?: number,
}>(), {
    serverSearchOptions: () => {
        return {}
    },
    drafts: () => []
});

const isLoading = ref(false);
const approvingIds = reactive(new Set<number>());
const suppressNextLoading = ref(false);
const { serverSearchOptions, accountId, accounts, transactions: verifiedTransactions } = toRefs(props);

// Merge verified and draft transactions for display
const displayTransactions = computed(() => {
    const draftsWithBadge = (props.drafts || []).map(t => ({
              ...t,
              _isDraft: true
    }));
    let allTransactions = [...verifiedTransactions.value, ...draftsWithBadge];

    // Filter by direction if set
    if (pageState.custom.direction) {
        allTransactions = allTransactions.filter(t => t.direction === pageState.custom.direction);
    }

    // Calculate running balance (transactions are newest-first)
    // End balance = startingBalance + sum of all movements in period
    if (props.startingBalance !== undefined) {
        const periodTotal = props.stats?.total ?? 0;
        let balance = (props.startingBalance ?? 0) + periodTotal;
        allTransactions = allTransactions.map(t => {
            const withBalance = { ...t, _runningBalance: balance };
            const amount = t.direction === 'WITHDRAW' ? -(t.total ?? 0) : (t.total ?? 0);
            balance -= amount;
            return withBalance;
        });
    }

    return allTransactions.map(t => ({ ...t, _viewingAccountId: props.accountId }));
});
const { state: pageState, hasFilters: baseHasFilters, reset: baseReset } =
    useServerSearch(serverSearchOptions);

const hasFilters = computed(() => {
    return baseHasFilters.value || Boolean(pageState.custom.direction);
});

const reset = () => {
    pageState.custom.direction = null;
    baseReset();
};

provide("selectedAccountId", accountId);

const selectedAccount = computed(() => {
    return accounts.value.find((account) => account.id === accountId?.value);
});

const context = useAppContextStore();
const listComponent = computed(() => {
    return context.isMobile ? TransactionSearch : TransactionTable;
});



const handleDuplicate = (transaction: ITransaction) => {
    axios.get(`/transactions/${transaction.id}?json=true`).then(({ data }) => {
        delete data.id;
        openTransactionModal({
            transactionData: data,
            mode: data.direction,
        });
    })
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
    openTransactionModal({
        transactionData: transaction,
    });
};

const handleApprove = (transaction: ITransaction) => {
    approvingIds.add(transaction.id);
    axios.post(`/finance/transactions/${transaction.id}/approve`)
        .then(() => {
            suppressNextLoading.value = true;
            router.reload({
                preserveState: true,
                preserveScroll: true,
                onFinish: () => {
                    approvingIds.delete(transaction.id);
                    suppressNextLoading.value = false;
                },
            });
        })
        .catch(() => {
            approvingIds.delete(transaction.id);
        });
};



onMounted(() => {
    router.on('start', () => {
        if (!suppressNextLoading.value) {
            isLoading.value = true;
        }
    })
    router.on('finish', () => isLoading.value = false)
})

const monthName = computed(() => format(pageState.dates.startDate, "MMMM"))

// ## Reconciliation


const hasReconciliation = computed(() => {
    return selectedAccount.value?.reconciliation_last
})

const hasPendingReconciliation = computed(() => {
    return selectedAccount.value?.reconciliation_last?.status == 'pending';
})

const isReconciled = computed(() => {
    return hasReconciliation.value && selectedAccount.value?.reconciliation_last.amount == selectedAccount.value.balance;
});


const reconcileForm = useForm({
    isVisible: false,
    date: new Date(),
    balance: 0,
})

const { TRANSFER } = TRANSACTION_DIRECTIONS;
const page = usePage().props;

// Credit cards
const currentBillingCycle = computed(() => {
    return props.billingCycles?.map((payment) => ({
        ...payment,
        date: payment.due_at
    }))?.at(0)
})
const creditCard = computed(() => {
    return props.accountDetailTypes.find((type) => type.label.toLowerCase() == "credit cards");
});

const isCreditCard = computed(() => {
    return selectedAccount.value?.account_detail_type_id == creditCard.value?.id;
});

// Credit-card utilization: debt / credit_limit as a percent. Personal-finance
// rule of thumb is to stay under 30% (green) and never above 80% (red) — the
// second-biggest factor in credit-score health after payment history.
// Hidden when there's no credit_limit set, which is also when this account
// likely isn't actually a credit card.
const utilization = computed(() => {
    const account = selectedAccount.value as any;
    if (!isCreditCard.value || !account?.credit_limit || account.credit_limit <= 0) {
        return null;
    }
    const limit = Number(account.credit_limit);
    const debt = Math.abs(Number(account.balance ?? 0));
    const percent = (debt / limit) * 100;
    return {
        debt,
        limit,
        percent: Math.min(percent, 100),
        rawPercent: percent,
    };
});

const utilizationColor = computed(() => {
    if (!utilization.value) return '';
    const p = utilization.value.percent;
    if (p < 30) return 'bg-emerald-500';
    if (p < 80) return 'bg-amber-500';
    return 'bg-red-500';
});

const utilizationLabelColor = computed(() => {
    if (!utilization.value) return '';
    const p = utilization.value.percent;
    if (p < 30) return 'text-emerald-700';
    if (p < 80) return 'text-amber-700';
    return 'text-red-700';
});

const payCreditCard = () => {
    const accountId = page.accountId
    const debt = Math.abs(selectedAccount.value?.balance ?? 0);
    openTransactionModal({
        mode: TRANSFER,
        transactionData: {
            counter_account_id: accountId ?? "",
            total: debt,
            description: `Payment of ${selectedAccount.value?.name}`,
            account_id: props.accounts.find((account) => account.balance > debt)?.id
        },
    })
}

// "Pay this cycle" from a NextPaymentsWidget row. Opens the transfer modal
// pre-filled with the cycle's REMAINING balance (total - paid) so partial
// pays auto-correct. The AutoLinkCreditCardPayment listener attaches the
// resulting Payment to the oldest open cycle for this card — usually the
// same one the user clicked. For users with multiple open cycles paying out
// of order, the existing link icon (or the manual setPaymentBill modal)
// still lets them target a specific cycle.
const payCycle = (cycle: any) => {
    const accountId = page.accountId;
    const total = Number(cycle.total ?? 0);
    const paid = Number(cycle.paid ?? 0);
    const remaining = Math.max(total - paid, 0);
    openTransactionModal({
        mode: TRANSFER,
        transactionData: {
            counter_account_id: accountId ?? "",
            total: remaining,
            description: `Payment of ${selectedAccount.value?.name} — cycle ${cycle.end_at ?? cycle.due_at ?? ''}`,
            account_id: props.accounts.find((account) => account.balance > remaining)?.id,
            date: cycle.due_at ?? undefined,
        },
    });
}

const setPaymentBill = (transaction: ITransaction) => {
    // Important: target the CLICKED cycle's id, not currentBillingCycle.at(0).
    // The latter was a long-standing bug that pointed every "record a payment"
    // request at whichever cycle happened to be first in the array regardless
    // of which row the user actually interacted with.
    openModal(
        {
            data: {
                documents: [transaction],
                resourceId: transaction.id,
                title: `Payment of ${transaction.name}`,
                defaultConcept: `Payment of ${transaction.name}`,
                due: transaction.total,
                transaction: transaction,
                endpoint: `/api/billing-cycles/${transaction.id}/payments/`,
                paymentMethod: paymentMethods[0],
            }
        })
}

// PDF Import
const showImportPdf = ref(false);
const importPdfForm = useForm<{ file: any }>({ file: null });
const submitPdfImport = () => {
    if (!importPdfForm.file || importPdfForm.processing) return;
    importPdfForm.post(`/finance/accounts/${accountId.value}/import-pdf`, {
        onSuccess() {
            showImportPdf.value = false;
            importPdfForm.reset();
        }
    });
};

// CSV Import
const showImportCsv = ref(false);
const importCsvForm = useForm<{ file: any }>({ file: null });
const submitCsvImport = () => {
    if (!importCsvForm.file || importCsvForm.processing) return;
    importCsvForm.post(`/finance/accounts/${accountId.value}/import-csv`, {
        onSuccess() {
            showImportCsv.value = false;
            importCsvForm.reset();
        }
    });
};

// More actions menu
const accountCsvExportUrl = computed(() => {
    const params = new URLSearchParams();
    const { startDate, endDate } = pageState.dates;
    if (startDate && endDate) {
        params.set('filter[date]', `${format(startDate, 'yyyy-MM-dd')}~${format(endDate, 'yyyy-MM-dd')}`);
    }
    if (accountId?.value) {
        params.set('filter[account_id]', String(accountId.value));
    }
    return `/finance/transactions/export/csv?${params.toString()}`;
});

const isSyncingEmails = ref(false);

const syncEmails = () => {
    if (isSyncingEmails.value) return;
    const { startDate, endDate } = pageState.dates;
    isSyncingEmails.value = true;
    router.post(`/finance/accounts/${accountId.value}/sync-emails`, {
        startDate: format(startDate, 'yyyy-MM-dd'),
        endDate: format(endDate, 'yyyy-MM-dd'),
    }, {
        preserveScroll: true,
        onFinish: () => { isSyncingEmails.value = false; },
    });
};

const moreActions = computed(() => {
    const actions: any[] = [
        { key: 'import-pdf', label: 'Import PDF' },
        { key: 'import-csv', label: 'Import CSV' },
        { key: 'export-csv', label: 'Export CSV' },
    ];
    if (selectedAccount.value?.bank_code) {
        actions.push({
            key: 'sync-emails',
            label: isSyncingEmails.value ? 'Syncing emails…' : `Sync ${monthName.value} emails`,
            disabled: isSyncingEmails.value,
        });
    }
    if (!isReconciled.value) {
        actions.push({ key: 'reconciliation', label: 'Reconciliation' });
    } else if (hasPendingReconciliation.value) {
        actions.push({ key: 'review-reconciliation', label: 'Review Reconciliation' });
    }
    if (isCreditCard.value) {
        actions.push({ key: 'pay-credit-card', label: 'Pay credit card' });
    }
    return actions;
});

const handleMoreAction = (key: string) => {
    switch (key) {
        case 'import-pdf': showImportPdf.value = true; break;
        case 'import-csv': showImportCsv.value = true; break;
        case 'export-csv': window.open(accountCsvExportUrl.value, '_blank'); break;
        case 'sync-emails': syncEmails(); break;
        case 'reconciliation': reconcileForm.isVisible = true; break;
        case 'review-reconciliation': router.visit(`/finance/reconciliation/${selectedAccount.value?.reconciliation_last?.id}`); break;
        case 'pay-credit-card': payCreditCard(); break;
    }
};

const transactionRowClass = (row: any) => {
    const classes: string[] = [];
    if (row._isDraft) {
        classes.push('border-l-4 border-l-amber-400');
    }
    if (approvingIds.has(row.id)) {
        classes.push('opacity-50 pointer-events-none');
    }
    return classes.join(' ');
};

const draftCount = computed(() => (props.drafts || []).length);

const financeTabs = computed(() => {
    const transactionLabel = draftCount.value > 0
        ? `${t('Transactions')} ${props.transactions.length} (${draftCount.value} ${t('pending')})`
        : `${t('Transactions')} ${props.transactions.length}`;

    return [{
        name: "transactions",
        label: transactionLabel,
    }];
});

const selectedTabName = computed(() => {
    return `${t('All transactions in')} ${monthName.value}`;
})

</script>

<template>
    <AppLayout @back="router.visit('/finance/transactions')" :show-back-button="true">
        <template #header>
            <FinanceSectionNav>
                <template #actions>
                    <div class="flex items-center w-full space-x-2">
                        <AtDatePager class="w-full h-12 border-none bg-base-lvl-1 text-body"
                            v-model:startDate="pageState.dates.startDate" v-model:endDate="pageState.dates.endDate"
                            controlsClass="bg-transparent text-body hover:bg-base-lvl-1" next-mode="month" />
                        <NDropdown trigger="click" key-field="key" :options="moreActions" @select="handleMoreAction">
                            <LogerButton variant="inverse">
                                <IMdiDotsVertical />
                            </LogerButton>
                        </NDropdown>
                    </div>
                </template>
            </FinanceSectionNav>
        </template>

        <template #title>
            <section class="flex items-center">
                <h1 class="font-bold">
                    <span class="text-body-1/60">Accounts / </span>
                    <span>{{ selectedAccount.name }}</span>
                </h1>
                <button @click="router.visit(`/finance/accounts/${selectedAccount.id}/reconciliations/`)"
                    title="reconciliations" class="inline-block ml-2 font-bold text-secondary">
                    <IMdiHistory />
                </button>
            </section>
        </template>

        <FinanceTemplate :title="$t('Transactions')" :accounts="accounts">
            <section class="mt-4 px-4 py-3 rounded-lg bg-base-lvl-3">
                <div class="flex items-center gap-4 flex-wrap">
                    <!-- Primary: Balance -->
                    <div class="flex items-center gap-2 mr-auto">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-secondary">{{ $t('Balance') }}</span>
                                <!-- Credit-card identity pill so the user knows what kind
                                     of account they're looking at without parsing the data. -->
                                <span
                                    v-if="isCreditCard"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-primary/10 text-primary"
                                >
                                    <IMdiCreditCard class="w-3 h-3" />
                                    {{ $t('Credit Card') }}
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-body">
                                {{ formatMoney(selectedAccount?.balance) }}
                            </h3>
                            <!-- Reconciliation freshness signal. Previously this was
                                 only visible as a clock icon with the amount in a
                                 tooltip — the *date* (the thing the user wants to
                                 know at a glance: "is this account stale?") was
                                 hidden. Click navigates to reconciliation history. -->
                            <button
                                v-if="selectedAccount?.reconciliation_last"
                                type="button"
                                class="mt-0.5 inline-flex items-center gap-1 text-[11px] text-body-1/60 hover:text-primary transition"
                                :title="$t('Open reconciliation history')"
                                @click="router.visit(`/finance/accounts/${selectedAccount.id}/reconciliations/`)"
                            >
                                <IMdiHistory class="w-3 h-3" />
                                <span>
                                    {{ $t('Last reconciled') }} · {{ formatDate(selectedAccount.reconciliation_last.date) }}
                                </span>
                                <span
                                    v-if="selectedAccount.reconciliation_last.status === 'pending'"
                                    class="ml-1 px-1 py-px rounded text-[9px] font-semibold uppercase tracking-wide bg-amber-100 text-amber-700"
                                >
                                    {{ $t('Pending') }}
                                </span>
                            </button>
                            <button
                                v-else
                                type="button"
                                class="mt-0.5 inline-flex items-center gap-1 text-[11px] text-body-1/50 hover:text-primary transition"
                                :title="$t('Start the first reconciliation for this account')"
                                @click="router.visit(`/finance/accounts/${selectedAccount?.id}/reconciliations/`)"
                            >
                                <IMdiHistory class="w-3 h-3" />
                                <span>{{ $t('Never reconciled') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Secondary stats: compact inline -->
                    <div class="flex items-center gap-4 text-sm text-body-1/80 flex-wrap">
                        <div class="text-center" v-if="startingBalance !== undefined">
                            <span class="block text-xs text-secondary">Start {{ monthName }}</span>
                            <span class="font-medium">{{ formatMoney(startingBalance) }}</span>
                        </div>
                        <div class="text-center" v-if="stats?.debit">
                            <span class="block text-xs text-secondary">{{ $t('Debit') }}</span>
                            <span class="font-medium text-red-500">{{ formatMoney(stats.debit) }}</span>
                        </div>
                        <div class="text-center" v-if="stats?.credit">
                            <span class="block text-xs text-secondary">{{ $t('Credit') }}</span>
                            <span class="font-medium text-green-600">{{ formatMoney(stats.credit) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Credit-card utilization bar — debt vs credit_limit. Green
                     under 30%, amber under 80%, red beyond. Standard personal-
                     finance rule of thumb for credit-score health. -->
                <div v-if="utilization" class="mt-3 pt-3 border-t border-base">
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="text-secondary">{{ $t('Utilization') }}</span>
                        <span class="font-medium tabular-nums" :class="utilizationLabelColor">
                            {{ utilization.percent.toFixed(0) }}% · {{ formatMoney(utilization.debt) }} {{ $t('of') }} {{ formatMoney(utilization.limit) }}
                        </span>
                    </div>
                    <div class="w-full h-1.5 rounded-full bg-base overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="utilizationColor"
                            :style="{ width: `${utilization.percent}%` }"
                        />
                    </div>
                    <p v-if="utilization.rawPercent >= 80" class="mt-1.5 text-[11px] text-red-600/80">
                        {{ $t('Above 80% utilization can hurt your credit score. Consider paying down some balance.') }}
                    </p>
                </div>
            </section>

            <!-- BHD-style "Detalle tarjeta de crédito" — two columns when the account
                 has multiple currencies. Reads from Account::getAllCurrencyBalances()
                 which is exposed as `all_currency_balances` on the account payload.
                 Hidden when the account is single-currency. -->
            <MultiCurrencyDetailPanel
                v-if="selectedAccount?.is_multi_currency && selectedAccount?.all_currency_balances?.length"
                class="mt-3"
                :account-name="selectedAccount.name"
                :account-type="isCreditCard ? 'credit_card' : (selectedAccount.detail_type?.name ?? 'bank')"
                :currencies="selectedAccount.all_currency_balances"
            />

            <AccountReconciliationBanner v-if="selectedAccount" :account="selectedAccount" class="mt-2" />

            <div
                v-if="displayTransactions.length === 0 && !isLoading"
                class="mt-3 flex flex-col sm:flex-row items-start sm:items-center gap-4 rounded-lg border border-primary/20 bg-primary/5 px-5 py-4"
            >
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10 text-primary flex-shrink-0">
                    <i class="fas fa-lightbulb" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm text-body-1">{{ $t('Get started with this account') }}</p>
                    <p class="text-xs text-body-1/60 mt-0.5">
                        {{ $t('Import your bank statement or add your first transaction manually.') }}
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <LogerButton
                        variant="inverse"
                        class="text-xs"
                        @click="showImportPdf = true"
                    >
                        <i class="fas fa-file-pdf mr-1" />
                        {{ $t('Import') }} PDF
                    </LogerButton>
                    <LogerButton
                        variant="inverse"
                        class="text-xs"
                        @click="showImportCsv = true"
                    >
                        <i class="fas fa-file-csv mr-1" />
                        {{ $t('Import') }} CSV
                    </LogerButton>
                    <LogerButton
                        variant="primary"
                        class="text-xs"
                        @click="openTransactionModal({})"
                    >
                        <i class="fas fa-plus mr-1" />
                        {{ $t('Add transaction') }}
                    </LogerButton>
                </div>
            </div>

            <WidgetContainer :message="selectedTabName" :tabs="financeTabs" default-tab="transactions" class="mt-4">
                <template #title>
                    <header class="flex space-x-2 pl-4 items-center justify-between py-2 w-full">
                        <AppSearch v-model.lazy="pageState.search" class="w-full md:flex " :has-filters="hasFilters"
                            @clear="reset()" :placeholder="selectedTabName" />

                        <!-- Debit/Credit Filter -->
                        <div class="flex space-x-1">
                            <button
                                @click="pageState.custom.direction = pageState.custom.direction === 'WITHDRAW' ? null : 'WITHDRAW'"
                                :class="[
                                    'px-3 py-1.5 text-xs rounded-full transition-colors',
                                    pageState.custom.direction === 'WITHDRAW'
                                        ? 'bg-red-100 text-red-700 border border-red-200'
                                        : 'bg-base-lvl-1 text-body-1 hover:bg-base-lvl-2'
                                ]">
                                {{ $t('Debits') }}
                            </button>
                            <button
                                @click="pageState.custom.direction = pageState.custom.direction === 'DEPOSIT' ? null : 'DEPOSIT'"
                                :class="[
                                    'px-3 py-1.5 text-xs rounded-full transition-colors',
                                    pageState.custom.direction === 'DEPOSIT'
                                        ? 'bg-green-100 text-green-700 border border-green-200'
                                        : 'bg-base-lvl-1 text-body-1 hover:bg-base-lvl-2'
                                ]">
                                {{ $t('Credits') }}
                            </button>
                        </div>

                    </header>
                </template>
                <template v-slot:content="{ selectedTab }">
                    <section class="bg-base-lvl-3">
                        <Component :is="listComponent" :cols="tableAccountCols(props.accountId)"
                            :transactions="displayTransactions" :server-search-options="serverSearchOptions"
                            :is-loading="isLoading" :empty-text="`No transactions in ${monthName}`"
                            :row-class="transactionRowClass" @findLinked="findLinked"
                            @removed="removeTransaction($event, ['verified'])" @duplicate="handleDuplicate"
                            @edit="handleEdit" @approved="handleApprove" />
                    </section>
                </template>
            </WidgetContainer>

            <template #prepend-panel class="">
                <section
                    v-if="isCreditCard"
                    class="w-full px-4 pt-4">
                    <div class="rounded-lg bg-base-lvl-3 px-4 py-3">
                        <header class="flex items-center justify-between text-xs text-body-1/70">
                            <span>{{ $t('Last payment') }}</span>
                            <span v-if="lastCreditCardPayment && !lastCreditCardPayment.is_linked"
                                class="text-warning">
                                {{ $t('Not linked to a cycle') }}
                            </span>
                        </header>
                        <div v-if="lastCreditCardPayment" class="mt-1 flex items-baseline justify-between gap-3">
                            <span class="text-lg font-semibold text-body">
                                {{ formatMoney(lastCreditCardPayment.amount, selectedAccount?.currency_code) }}
                            </span>
                            <span class="text-xs text-body-1">
                                {{ formatDate(lastCreditCardPayment.date) }}
                            </span>
                        </div>
                        <div v-if="lastCreditCardPayment?.source_account" class="mt-1 text-xs text-body-1/70 truncate">
                            {{ $t('From') }} {{ lastCreditCardPayment.source_account }}
                        </div>
                        <p v-if="!lastCreditCardPayment" class="mt-1 text-sm text-body-1">
                            {{ $t('No payments yet') }}
                        </p>
                    </div>
                </section>
                <NextPaymentsWidget class="w-full py-4 px-4" :title="$t('Credit Card Payments')" :payments="billingCycles.map((payment) => ({
                    ...payment,
                    date: payment.due_at
                }))" emit-actions emit-delete @action="setPaymentBill" @pay="payCycle">
                    <template v-slot:left-action-button="{ resource }">
                        <button
                            class="text-gray-400 hidden group-hover:inline-block transition cursor-pointer hover:text-red-400 focus:outline-none"
                            @click="setPaymentBill(resource)">
                            <IMdiLink />
                        </button>
                    </template>
                    <template v-slot:date="{ resource }">
                        <span title="Approve transaction"
                            class="text-secondary bg-secondary/10 px-4 rounded-3xl py-1.5 text-xs cursor-pointer"
                            @click="$emit('edit', payment)">
                            {{ formatDate(resource.date) }}
                        </span>
                    </template>
                </NextPaymentsWidget>
            </template>

            <AccountReconciliationForm :show="reconcileForm.isVisible" @close="reconcileForm.isVisible = false"
                :account="selectedAccount" />
        </FinanceTemplate>

        <Modal :show="showImportPdf" max-width="lg" :closeable="true" :is-open="showImportPdf" :automatic="false" :full-height="false" @close="showImportPdf = false">
            <header class="flex items-center px-6 py-4 font-bold bg-base-lvl-3">
                {{ $t('Import PDF Statement') }}
            </header>
            <section class="px-6 py-4 bg-base-lvl-3 text-body">
                <p class="mb-4 text-sm text-body-1/80">
                    {{ $t('Upload a bank statement PDF to import transactions as drafts into this account.') }}
                </p>
                <ImportHolder
                    v-model:file="importPdfForm.file"
                    :endpoint="`/finance/accounts/${accountId}/import-pdf`"
                    :processing="importPdfForm.processing"
                    :placeholder="$t('Drag a PDF bank statement here or click to browse')"
                />
            </section>
            <footer class="flex justify-end px-6 py-4 space-x-3 bg-base">
                <LogerButton variant="secondary" class="h-10" @click="showImportPdf = false"
                    :disabled="importPdfForm.processing">
                    {{ $t('Cancel') }}
                </LogerButton>
                <LogerButton class="h-10 text-white bg-primary" @click="submitPdfImport"
                    :disabled="!importPdfForm.file || importPdfForm.processing"
                    :processing="importPdfForm.processing">
                    {{ $t('Import') }}
                </LogerButton>
            </footer>
        </Modal>

        <Modal :show="showImportCsv" max-width="lg" :closeable="true" :is-open="showImportCsv" :automatic="false" :full-height="false" @close="showImportCsv = false">
            <header class="flex items-center px-6 py-4 font-bold bg-base-lvl-3">
                {{ $t('Import CSV Statement') }}
            </header>
            <section class="px-6 py-4 bg-base-lvl-3 text-body">
                <p class="mb-4 text-sm text-body-1/80">
                    {{ $t('Upload a bank statement CSV to import transactions as drafts into this account.') }}
                </p>
                <ImportHolder
                    v-model:file="importCsvForm.file"
                    :endpoint="`/finance/accounts/${accountId}/import-csv`"
                    :processing="importCsvForm.processing"
                    :placeholder="$t('Drag a CSV bank statement here or click to browse')"
                    accept=".csv,.txt"
                />
            </section>
            <footer class="flex justify-end px-6 py-4 space-x-3 bg-base">
                <LogerButton variant="secondary" class="h-10" @click="showImportCsv = false"
                    :disabled="importCsvForm.processing">
                    {{ $t('Cancel') }}
                </LogerButton>
                <LogerButton class="h-10 text-white bg-primary" @click="submitCsvImport"
                    :disabled="!importCsvForm.file || importCsvForm.processing"
                    :processing="importCsvForm.processing">
                    {{ $t('Import') }}
                </LogerButton>
            </footer>
        </Modal>
    </AppLayout>
</template>

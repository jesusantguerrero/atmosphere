<script setup lang="ts">
import { computed, toRefs, provide, reactive, ref, onMounted } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { addMonths, endOfMonth, format, isSameMonth, startOfMonth } from "date-fns";
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
import AccountReconciliationForm from "./AccountReconciliationForm.vue";

import { NDatePicker, NDropdown } from "naive-ui";

import { useTransactionModal, TRANSACTION_DIRECTIONS, removeTransaction } from "@/domains/transactions";
import { tableAccountCols } from "@/domains/transactions";
import { paymentMethods } from "@/domains/transactions/constants";
import { useAppContextStore } from "@/store";
import { formatDate, formatMoney } from "@/utils";
import { IAccount, ICategory, ITransaction } from "@/domains/transactions/models";
import NextPaymentsWidget from "@/domains/transactions/components/NextPaymentsWidget.vue";
import { usePaymentModal } from "@/domains/transactions/usePaymentModal";
import Modal from "@/Components/atoms/Modal.vue";
import ImportHolder from "@/Components/organisms/ImportHolder.vue";

const { openTransactionModal } = useTransactionModal();
const { openModal } = usePaymentModal();


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

// Register filter: All / Debits / Credits. Replaces the old pill chips with a
// single proportioned segmented control that sits inline with search.
const directionOptions: { label: string, value: string | null }[] = [
    { label: 'All', value: null },
    { label: 'Debits', value: 'WITHDRAW' },
    { label: 'Credits', value: 'DEPOSIT' },
];
const isDirection = (value: string | null): boolean => (pageState.custom.direction ?? null) === value;

// Register sorting. The running balance is calculated in the server's date order
// BEFORE this sort runs, so each row keeps the balance it actually had at that
// point in time — re-ordering the view never rewrites those numbers.
const sort = ref<{ name: string | null, dir: 'asc' | 'desc' }>({ name: null, dir: 'asc' });

const sortValue = (row: any, key: string) => {
    switch (key) {
        case 'date': return row.date ?? '';
        case 'payee': return (row.payee?.name ?? row.payee_name ?? '').toLowerCase();
        case 'total': return Number(row.total ?? 0);
        case 'type': return row.is_transfer ? 'Transfer' : (row.direction === 'DEPOSIT' ? 'Income' : 'Expense');
        default: return row[key];
    }
};

const sortedTransactions = computed(() => {
    const rows = displayTransactions.value;
    if (!sort.value.name) {
        return rows;
    }

    const direction = sort.value.dir === 'asc' ? 1 : -1;
    const key = sort.value.name;

    return [...rows].sort((a, b) => {
        const left = sortValue(a, key);
        const right = sortValue(b, key);
        if (left === right) return 0;
        return left > right ? direction : -direction;
    });
});

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

// Period navigation. The old AtDatePager could only step one month at a time, so
// reaching an arbitrary month (say Aug 2024) took ~20 clicks — and 20 more to get
// back. The month/year picker jumps anywhere directly and "Today" is a one-click
// return to the current month.
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

// --- Cashback quick-add (credit cards only) ---
// Records a cashback as a normal income (DEPOSIT) transaction on this card,
// defaulting to the "Ready to Assign" category. Reuses the same
// transactions.store endpoint the modal uses, just with cashback defaults.
const READY_TO_ASSIGN_NAME = "Ready to Assign";
const showCashback = ref(false);
const cashbackAmount = ref<string>("");
const cashbackNote = ref<string>("");
const cashbackDate = ref<string>(format(new Date(), "yyyy-MM-dd"));
const cashbackCategoryId = ref<number | null>(null);
const cashbackExpanded = ref(false);
const savingCashback = ref(false);
const cashbackCategoryName = computed(
    () => props.categories?.find((c: any) => c.id === cashbackCategoryId.value)?.name ?? READY_TO_ASSIGN_NAME
);
const openCashback = (): void => {
    cashbackAmount.value = "";
    cashbackDate.value = format(new Date(), "yyyy-MM-dd");
    cashbackExpanded.value = false;
    cashbackCategoryId.value = props.categories?.find((c: any) => c.name === READY_TO_ASSIGN_NAME)?.id ?? null;
    cashbackNote.value = `Cashback · ${selectedAccount.value?.name ?? ""}`.trim();
    showCashback.value = true;
};
const submitCashback = (): void => {
    const amount = Number(cashbackAmount.value);
    if (!amount || savingCashback.value) {
        return;
    }
    savingCashback.value = true;
    router.post(
        route("transactions.store"),
        {
            resource_type_id: "MANUAL",
            status: "verified",
            direction: "DEPOSIT",
            date: cashbackDate.value,
            description: cashbackNote.value || `Cashback · ${selectedAccount.value?.name ?? ""}`,
            account_id: selectedAccount.value?.id,
            category_id: cashbackCategoryId.value,
            total: amount,
            has_splits: false,
            counter_account_id: null,
            currency_code: selectedAccount.value?.currency_code,
            is_multi_currency: false,
            payee_id: null,
            label_id: null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showCashback.value = false;
                cashbackAmount.value = "";
            },
            onFinish: () => {
                savingCashback.value = false;
            },
        }
    );
};

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
    return 'text-error';
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

const importActions = [
    { key: 'import-pdf', label: 'Import PDF' },
    { key: 'import-csv', label: 'Import CSV' },
];

const moreActions = computed(() => {
    const actions: any[] = [
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


</script>

<template>
    <AppLayout @back="router.visit('/finance/transactions')" :show-back-button="true">
        <!-- The tabs row navigates, and nothing else: period/Import/kebab moved
             down to the register card's toolbar, where the controls actually
             apply. Keeps the sub-nav a single-purpose, quiet row. -->
        <template #header>
            <FinanceSectionNav />
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

        <!-- hide-panel: the register runs full width, with no side column. The
             reference design is a single focused register — the accounts ledger and
             credit-card widgets that used to sit on the right were the main source
             of visual noise here. -->
        <FinanceTemplate :title="$t('Transactions')" :accounts="accounts" :hide-panel="true">
            <!-- Page header: the balance IS the page title now (left), with the
                 view's actions on the right. One filled primary per view
                 (Reconcile); Cashback stays as a quiet tinted-outline secondary. -->
            <section class="mt-4 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <div class="text-xs text-secondary">
                        {{ isCreditCard ? $t('Available credit') : $t('Cleared Balance') }}
                    </div>
                    <div class="text-2xl font-bold leading-tight text-body tabular-nums">
                        {{ isCreditCard && utilization ? formatMoney(utilization.limit - utilization.debt) : formatMoney(selectedAccount?.balance) }}
                    </div>
                    <div v-if="isCreditCard" class="text-xs text-body-1/50 tabular-nums">{{ $t('Balance') }} {{ formatMoney(selectedAccount?.balance) }}</div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-if="isCreditCard"
                        type="button"
                        class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-sm font-semibold text-success border border-success/25 bg-success/5 hover:border-success/50 transition"
                        :title="$t('Add cashback')"
                        @click="openCashback"
                    >
                        <i class="fas fa-plus text-xs" />
                        {{ $t('Cashback') }}
                    </button>
                    <LogerButton variant="primary" @click="reconcileForm.isVisible = true">
                        <i class="fas fa-check-double mr-1.5" />
                        {{ $t('Reconcile Now') }}
                    </LogerButton>
                </div>
            </section>

        <Teleport to="body">
            <div v-if="showCashback" class="fixed inset-0 z-[1400]" @click="showCashback = false" />
            <div
                v-if="showCashback"
                class="fixed z-[1401] top-24 right-6 w-80 max-w-[92vw] bg-base-lvl-3 border border-base rounded-2xl shadow-2xl p-4"
            >
                <h4 class="font-bold text-body mb-0.5 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-md bg-success/15 text-success flex items-center justify-center text-xs font-bold">%</span>
                    {{ $t('Add cashback') }}
                </h4>
                <p class="text-[11px] text-body-1/50 mb-3">{{ $t('Recorded as income on this card') }}</p>

                <div class="flex items-center gap-2 bg-base-lvl-2 border border-base rounded-lg px-3 py-2.5 mb-2">
                    <span class="text-xs text-body-1/50 font-semibold">{{ selectedAccount?.currency_code }}</span>
                    <input
                        v-model="cashbackAmount"
                        type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00"
                        class="flex-1 w-full bg-transparent outline-none text-body text-xl font-bold tabular-nums"
                        @keydown.enter="submitCashback"
                    />
                </div>

                <div class="bg-base-lvl-2 border border-base rounded-lg px-3 py-2 mb-2">
                    <label class="block text-[9px] uppercase tracking-wide text-body-1/50 mb-0.5">{{ $t('Note') }}</label>
                    <input v-model="cashbackNote" type="text" class="w-full bg-transparent outline-none text-body text-sm font-medium" />
                </div>

                <div class="bg-base-lvl-2 border border-base rounded-lg px-3 py-2 mb-2">
                    <label class="block text-[9px] uppercase tracking-wide text-body-1/50 mb-0.5">{{ $t('Date') }}</label>
                    <input v-model="cashbackDate" type="date" class="w-full bg-transparent outline-none text-body text-sm font-medium" />
                </div>

                <p class="text-[11px] text-body-1/60 px-1 mb-1">
                    &rarr; {{ $t('goes to') }} <span class="text-body-1/90 font-medium">{{ cashbackCategoryName }}</span>
                </p>

                <button type="button" class="text-xs text-body-1/70 hover:text-body px-1 py-1" @click="cashbackExpanded = !cashbackExpanded">
                    {{ cashbackExpanded ? '&#9650;' : '&#9660;' }} {{ $t('More options') }}
                </button>
                <div v-if="cashbackExpanded" class="border-t border-dashed border-base pt-2 mt-1">
                    <label class="block text-[9px] uppercase tracking-wide text-body-1/50 mb-1 px-1">{{ $t('Category') }}</label>
                    <select v-model="cashbackCategoryId" class="w-full bg-base-lvl-2 border border-base rounded-lg px-3 py-2 text-sm text-body outline-none">
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 mt-3">
                    <button type="button" class="text-sm text-body-1 px-3 py-2 rounded-lg hover:bg-base-lvl-2 transition" @click="showCashback = false">
                        {{ $t('Cancel') }}
                    </button>
                    <button
                        type="button"
                        class="flex-1 bg-success text-white font-semibold text-sm py-2.5 rounded-lg disabled:opacity-50 transition"
                        :disabled="!Number(cashbackAmount) || savingCashback"
                        @click="submitCashback"
                    >
                        {{ savingCashback ? $t('Saving…') : $t('Add cashback') }}
                    </button>
                </div>
            </div>
        </Teleport>

            <!-- Multi-currency detail panel — hidden in Phase 1 to keep the register
                 clean. Reintroduce (behind a toggle/menu) in a later phase.
            <MultiCurrencyDetailPanel
                v-if="selectedAccount?.is_multi_currency && selectedAccount?.all_currency_balances?.length"
                class="mt-3"
                :account-name="selectedAccount.name"
                :account-type="isCreditCard ? 'credit_card' : (selectedAccount.detail_type?.name ?? 'bank')"
                :currencies="selectedAccount.all_currency_balances"
            /> -->

            <AccountReconciliationBanner v-if="selectedAccount" :account="selectedAccount" class="mt-2" />

            <!-- Plain card instead of WidgetContainer: its header only restated the
                 period ("All transactions in May") and a single-tab pill with the
                 count — both already shown by the period picker above and the footer
                 below. That was a full row of chrome saying nothing new. -->
            <article class="mt-4 overflow-hidden border rounded-lg bg-base-lvl-3 border-base">
                <!-- Card toolbar: everything that filters or feeds THIS list lives
                     here — search, direction filter, period pager, Import, kebab.
                     Navigation (tabs) and page actions (Reconcile) stay above. -->
                <header class="flex flex-col gap-3 px-4 py-2.5 border-b border-base md:flex-row md:items-center">
                    <AppSearch
                        v-model.lazy="pageState.search"
                        class="w-full md:max-w-xs"
                        :has-filters="hasFilters"
                        :placeholder="$t('Search')"
                        @clear="reset()"
                    />

                    <div class="inline-flex self-start p-0.5 text-xs rounded-lg bg-base-lvl-1 md:self-auto">
                        <button
                            v-for="opt in directionOptions"
                            :key="String(opt.value)"
                            type="button"
                            class="px-3 py-1.5 rounded-md transition-colors"
                            :class="isDirection(opt.value)
                                ? 'bg-base-lvl-3 text-body font-semibold shadow-sm'
                                : 'text-body-1/60 hover:text-body'"
                            @click="pageState.custom.direction = opt.value"
                        >
                            {{ $t(opt.label) }}
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-1 md:ml-auto">
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

                        <!-- No "+ Transaction" here on purpose: the global "+ New" in
                             the app header already opens the same modal, prefilled with
                             this account AND with a type to pick. -->
                        <NDropdown trigger="click" key-field="key" :options="importActions" @select="handleMoreAction">
                            <LogerButton variant="neutral" class="ml-1 !px-3 !py-1.5 text-xs">
                                <i class="fas fa-file-import mr-1.5" />
                                {{ $t('Import') }}
                            </LogerButton>
                        </NDropdown>
                        <NDropdown trigger="click" key-field="key" :options="moreActions" @select="handleMoreAction">
                            <button type="button" class="px-2 py-1.5 rounded text-body-1 hover:bg-base-lvl-2" :title="$t('More actions')">
                                <IMdiDotsVertical />
                            </button>
                        </NDropdown>
                    </div>
                </header>

                <Component :is="listComponent" :cols="tableAccountCols(props.accountId)"
                    :transactions="sortedTransactions" :server-search-options="serverSearchOptions"
                    :is-loading="isLoading" :empty-text="`No transactions in ${monthName}`"
                    :row-class="transactionRowClass" @findLinked="findLinked"
                    @removed="removeTransaction($event, ['verified'])" @duplicate="handleDuplicate"
                    @edit="handleEdit" @approved="handleApprove" @sort="sort = $event">
                    <template #empty>
                        <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                            <div class="flex items-center justify-center mb-4 rounded-full w-14 h-14 bg-primary/10 text-primary">
                                <i class="text-2xl fas fa-receipt" />
                            </div>
                            <h3 class="mb-1 text-lg font-bold text-body-1">
                                {{ hasFilters ? $t('No matching transactions') : `${$t('No transactions in')} ${monthName}` }}
                            </h3>
                            <p class="max-w-xs mb-5 text-sm text-body-1/60">
                                {{ hasFilters
                                    ? $t('Try adjusting your search or filters.')
                                    : $t('Import a bank statement to bring this account up to date.') }}
                            </p>

                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <LogerButton v-if="hasFilters" variant="inverse" class="text-xs" @click="reset()">
                                    <i class="mr-1 fas fa-times" />
                                    {{ $t('Clear filters') }}
                                </LogerButton>
                                <template v-else>
                                    <LogerButton variant="inverse" class="text-xs" @click="showImportPdf = true">
                                        <i class="mr-1 fas fa-file-pdf" />
                                        {{ $t('Import') }} PDF
                                    </LogerButton>
                                    <LogerButton variant="inverse" class="text-xs" @click="showImportCsv = true">
                                        <i class="mr-1 fas fa-file-csv" />
                                        {{ $t('Import') }} CSV
                                    </LogerButton>
                                </template>
                            </div>
                        </div>
                    </template>
                </Component>

                <footer
                    v-if="displayTransactions.length"
                    class="flex items-center justify-end gap-2 px-5 py-2.5 text-xs font-semibold border-t text-body-1/60 border-base"
                >
                    <template v-if="draftCount">
                        <span class="text-warning">{{ draftCount }} {{ $t('pending') }}</span>
                        <span class="text-body-1/30">·</span>
                    </template>
                    <span>
                        {{ displayTransactions.length }}
                        {{ displayTransactions.length === 1 ? $t('Transaction') : $t('Transactions') }}
                    </span>
                </footer>
            </article>

            <!-- Credit-card side widgets (last payment + upcoming cycles) are parked
                 while the register runs full width. With hide-panel on, this slot
                 renders ABOVE the register, which reintroduces the clutter we just
                 removed — so it stays off until it gets a proper home (a collapsible
                 strip or the "…" menu).
            <template #prepend-panel>
                <section
                    v-if="isCreditCard"
                    class="w-full pt-4">
                    <div class="rounded-lg bg-base-lvl-3 px-4 py-3">
                        <header class="flex items-center justify-between text-xs text-body-1/70">
                            <span>{{ $t('Last payment') }}</span>
                            <span v-if="lastCreditCardPayment && !lastCreditCardPayment.is_linked"
                                class="text-warning">
                                {{ $t('Not linked to a cycle') }}
                            </span>
                        </header>
                        <div v-if="lastCreditCardPayment" class="mt-1 flex items-baseline justify-between gap-3 flex-wrap">
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
                <NextPaymentsWidget class="w-full" :title="$t('Credit Card Payments')" :payments="billingCycles.map((payment) => ({
                    ...payment,
                    date: payment.due_at
                }))" emit-actions emit-delete @action="setPaymentBill" @pay="payCycle">
                    <template v-slot:left-action-button="{ resource }">
                        <button
                            class="text-body-1/70 hidden group-hover:inline-block transition cursor-pointer hover:text-error focus:outline-none"
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
            -->

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

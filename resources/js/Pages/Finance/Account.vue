<script setup lang="ts">
import { computed, toRefs, provide, ref, onMounted } from "vue";
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


const props = withDefaults(defineProps<{
    accountDetailTypes: { label: string, id: number | string }[];
    transactions: ITransaction[];
    drafts?: ITransaction[];
    billingCycles: ITransaction[];
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

    return allTransactions;
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
    axios.post(`/finance/transactions/${transaction.id}/approve`).then(() => {
        router.reload();
    });
};



onMounted(() => {
    router.on('start', () => isLoading.value = true)
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

const setPaymentBill = (transaction: ITransaction) => {
    openModal(
        {
            data: {
                documents: [transaction],
                resourceId: transaction.id,
                title: `Payment of ${transaction.name}`,
                defaultConcept: `Payment of ${transaction.name}`,
                due: transaction.total,
                transaction: transaction,
                endpoint: `/api/billing-cycles/${currentBillingCycle.value.id}/payments/`,
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

// More actions menu
const moreActions = computed(() => {
    const actions: any[] = [
        { key: 'import-pdf', label: 'Import PDF' },
    ];
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
        case 'reconciliation': reconcileForm.isVisible = true; break;
        case 'review-reconciliation': router.visit(`/finance/reconciliation/${selectedAccount.value?.reconciliation_last?.id}`); break;
        case 'pay-credit-card': payCreditCard(); break;
    }
};

const transactionRowClass = (row: any) => {
    return row._isDraft ? 'border-l-4 border-l-amber-400' : '';
};

const draftCount = computed(() => (props.drafts || []).length);

const financeTabs = computed(() => {
    const transactionLabel = draftCount.value > 0
        ? `Transactions ${props.transactions.length} (${draftCount.value} pending)`
        : `Transactions ${props.transactions.length}`;

    return [{
        name: "transactions",
        label: transactionLabel,
    }];
});

const selectedTabName = computed(() => {
    return `All transactions in ${monthName.value}`;
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

        <FinanceTemplate title="Transactions" :accounts="accounts">
            <section class="flex items-center gap-4 mt-4 px-4 py-3 rounded-lg bg-base-lvl-3 flex-wrap">
                <!-- Primary: Balance -->
                <div class="flex items-center gap-2 mr-auto">
                    <div>
                        <span class="text-xs text-secondary">{{ $t('Balance') }}</span>
                        <h3 class="text-xl font-bold text-body">
                            {{ formatMoney(selectedAccount?.balance) }}
                        </h3>
                    </div>
                    <ElTooltip :content="formatMoney(selectedAccount?.reconciliation_last?.amount)"
                        v-if="selectedAccount?.reconciliation_last">
                        <button
                            @click="router.visit(`/finance/accounts/${selectedAccount.id}/reconciliations/`)"
                            class="text-secondary hover:text-primary transition-colors">
                            <IMdiHistory />
                        </button>
                    </ElTooltip>
                </div>

                <!-- Secondary stats: compact inline -->
                <div class="flex items-center gap-4 text-sm text-body-1/80 flex-wrap">
                    <div class="text-center" v-if="startingBalance !== undefined">
                        <span class="block text-xs text-secondary">Start {{ monthName }}</span>
                        <span class="font-medium">{{ formatMoney(startingBalance) }}</span>
                    </div>
                    <div class="text-center" v-if="stats?.debit">
                        <span class="block text-xs text-secondary">Debit</span>
                        <span class="font-medium text-red-500">{{ formatMoney(stats.debit) }}</span>
                    </div>
                    <div class="text-center" v-if="stats?.credit">
                        <span class="block text-xs text-secondary">Credit</span>
                        <span class="font-medium text-green-600">{{ formatMoney(stats.credit) }}</span>
                    </div>
                </div>
            </section>

            <AccountReconciliationBanner v-if="selectedAccount" :account="selectedAccount" class="mt-2" />

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
                                Débitos
                            </button>
                            <button
                                @click="pageState.custom.direction = pageState.custom.direction === 'DEPOSIT' ? null : 'DEPOSIT'"
                                :class="[
                                    'px-3 py-1.5 text-xs rounded-full transition-colors',
                                    pageState.custom.direction === 'DEPOSIT'
                                        ? 'bg-green-100 text-green-700 border border-green-200'
                                        : 'bg-base-lvl-1 text-body-1 hover:bg-base-lvl-2'
                                ]">
                                Créditos
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
                <NextPaymentsWidget class="w-full py-4 px-4" title="Credit Card Payments" :payments="billingCycles.map((payment) => ({
                    ...payment,
                    date: payment.due_at
                }))" emit-actions emit-delete @action="setPaymentBill">
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
                Import PDF Statement
            </header>
            <section class="px-6 py-4 bg-base-lvl-3 text-body">
                <p class="mb-4 text-sm text-body-1/80">
                    Upload a bank statement PDF (BHD format) to import transactions as drafts into this account.
                </p>
                <ImportHolder
                    v-model:file="importPdfForm.file"
                    :endpoint="`/finance/accounts/${accountId}/import-pdf`"
                    :processing="importPdfForm.processing"
                    placeholder="Drag a PDF bank statement here or click to browse"
                />
            </section>
            <footer class="flex justify-end px-6 py-4 space-x-3 bg-base">
                <LogerButton variant="secondary" class="h-10" @click="showImportPdf = false"
                    :disabled="importPdfForm.processing">
                    Cancel
                </LogerButton>
                <LogerButton class="h-10 text-white bg-primary" @click="submitPdfImport"
                    :disabled="!importPdfForm.file || importPdfForm.processing"
                    :processing="importPdfForm.processing">
                    Import
                </LogerButton>
            </footer>
        </Modal>
    </AppLayout>
</template>

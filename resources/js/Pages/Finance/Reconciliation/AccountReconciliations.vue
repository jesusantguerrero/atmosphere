<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import BackgroundCard from "@/Components/molecules/BackgroundCard.vue";

import FinanceTemplate from "../Partials/FinanceTemplate.vue";
import FinanceSectionNav from "../Partials/FinanceSectionNav.vue";
import TransactionTable from "@/domains/transactions/components/TransactionTable.vue";
import AccountReconciliationForm from "../AccountReconciliationForm.vue";

import { tableAccountCols, removeTransaction } from "@/domains/transactions";
import { formatDate, formatMoney } from "@/utils";
import { IAccount, ITransaction } from "@/domains/transactions/models";

interface IReconciliation {
    id: number;
    date: string;
    amount: number;
    difference: number;
    status: string;
    total_transactions: number;
    matched_transactions: number;
}

const props = withDefaults(defineProps<{
    transactions: any[];
    previewTransactions: ITransaction[];
    lastReconciliation: IReconciliation | null;
    reconciliations: IReconciliation[];
    account: IAccount;
    accounts: IAccount[];
}>(), {
    previewTransactions: () => [],
});

const isLoading = ref(false);
const showAll = ref(false);
const showReconcileForm = ref(false);

onMounted(() => {
    router.on('start', () => isLoading.value = true);
    router.on('finish', () => isLoading.value = false);
});

// Reconciliations sorted chronologically (oldest first) for the timeline
const visibleReconciliations = computed(() => {
    const sorted = [...props.reconciliations].reverse();
    return showAll.value ? sorted : sorted.slice(-4);
});

const lastReconciliationDate = computed(() => {
    return props.lastReconciliation?.date;
});

const hasPendingReconciliation = computed(() => {
    return props.reconciliations.some((r) => r.status !== 'completed');
});

const pendingReconciliation = computed(() => {
    return props.reconciliations.find((r) => r.status !== 'completed');
});

// Stats
const totalReconciliations = computed(() => props.reconciliations.length);

const lastStatementBalance = computed(() => {
    return props.lastReconciliation?.amount ?? 0;
});

const lastDifference = computed(() => {
    return props.lastReconciliation?.difference ?? 0;
});

const unreconciledCount = computed(() => props.transactions.length);

// Actions
const deleteReconciliation = (reconciliation: IReconciliation) => {
    const confirmed = confirm("Are you sure you want to delete this reconciliation?");
    if (confirmed) {
        router.delete(`/finance/reconciliation/${reconciliation.id}`, {
            onSuccess() {
                router.reload();
            },
        });
    }
};

const navigateToReconciliation = (reconciliation: IReconciliation) => {
    router.visit(`/finance/reconciliation/${reconciliation.id}`);
};

const startReconciliation = () => {
    showReconcileForm.value = true;
};

const pluralize = (count: number, singular: string, plural: string) => {
    return count === 1 ? `${count} ${singular}` : `${count} ${plural}`;
};
</script>

<template>
    <AppLayout @back="router.visit(`/finance/accounts/${account.id}`)" :title="account.name" :show-back-button="true">
        <template #header>
            <FinanceSectionNav />
        </template>
        <template #title>
            <section class="flex items-center">
                <h1 class="font-bold">
                    <span class="text-body-1/60 cursor-pointer hover:underline"
                        @click="router.visit(`/finance/accounts/${account.id}`)">
                        {{ account.name }}
                    </span>
                    <span class="text-body-1/60"> / </span>
                    <span>Reconciliations</span>
                </h1>
            </section>
        </template>

        <FinanceTemplate :title="$t('Reconciliation History')" :accounts="accounts">
            <!-- Stats Row -->
            <section class="grid grid-cols-2 lg:grid-cols-4 gap-2 lg:gap-4 mt-4">
                <BackgroundCard
                    :value="formatMoney(account.balance, account.currency_code)"
                    label="Account Balance"
                    label-class="capitalize text-secondary"
                />
                <BackgroundCard
                    :value="formatMoney(lastStatementBalance, account.currency_code)"
                    label="Last Statement Balance"
                    label-class="capitalize text-secondary"
                />
                <BackgroundCard
                    :value="pluralize(unreconciledCount, 'transaction', 'transactions')"
                    label="Unreconciled"
                    text-class="text-xl font-bold"
                    :label-class="unreconciledCount > 0 ? 'text-warning font-semibold' : 'capitalize text-secondary'"
                />
                <BackgroundCard
                    :value="String(totalReconciliations)"
                    label="Total Reconciliations"
                    text-class="text-xl font-bold"
                    label-class="capitalize text-secondary"
                />
            </section>

            <!-- Reconciliation Timeline -->
            <section class="bg-base-lvl-3 p-6 rounded-lg shadow mt-4">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-body">Reconciliation Timeline</h2>
                        <p class="text-sm text-body-1/60 mt-1" v-if="lastReconciliationDate">
                            Reconciled up to
                            <span class="font-semibold text-body-1">{{ formatDate(lastReconciliationDate) }}</span>
                        </p>
                        <p class="text-sm text-body-1/60 mt-1" v-else>
                            No reconciliations yet
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <LogerButton variant="inverse" @click="startReconciliation">
                            Reconcile new transactions
                        </LogerButton>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="relative flex items-start justify-between mt-4 mb-2 overflow-x-auto pb-4"
                    v-if="reconciliations.length">
                    <!-- Horizontal line -->
                    <div class="absolute left-0 right-0 top-6 h-0.5 bg-gray-200 z-0"></div>

                    <!-- Timeline items -->
                    <template v-for="(reconciliation, idx) in visibleReconciliations" :key="reconciliation.id">
                        <div class="flex flex-col items-center z-10 min-w-[140px] cursor-pointer group"
                            @click="navigateToReconciliation(reconciliation)">
                            <!-- Status icon -->
                            <div
                                class="rounded-full border-4 w-12 h-12 flex items-center justify-center mb-2 shadow transition-transform group-hover:scale-110"
                                :class="{
                                    'border-green-400 bg-green-50': reconciliation.status === 'completed',
                                    'border-yellow-400 bg-yellow-50': reconciliation.status !== 'completed'
                                }">
                                <svg v-if="reconciliation.status === 'completed'" class="w-6 h-6 text-green-500"
                                    fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg v-else class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="12" y1="8" x2="12" y2="12" />
                                    <line x1="12" y1="16" x2="12.01" y2="16" />
                                </svg>
                            </div>

                            <!-- Date -->
                            <span class="text-sm font-semibold text-body group-hover:text-primary transition-colors">
                                {{ formatDate(reconciliation.date) }}
                            </span>

                            <!-- Summary details -->
                            <span class="text-xs text-body-1/60 mt-1">
                                {{ formatMoney(reconciliation.amount, account.currency_code) }}
                            </span>
                            <span class="text-xs mt-0.5" :class="{
                                'text-green-600': reconciliation.difference === 0,
                                'text-red-500 font-semibold': reconciliation.difference !== 0
                            }">
                                <template v-if="reconciliation.difference === 0">Balanced</template>
                                <template v-else>Diff: {{ formatMoney(reconciliation.difference, account.currency_code)
                                    }}</template>
                            </span>
                            <span class="text-xs text-body-1/60 mt-0.5">
                                {{ reconciliation.matched_transactions }}/{{ reconciliation.total_transactions }}
                                matched
                            </span>

                            <!-- Delete button (only pending) -->
                            <button v-if="reconciliation.status !== 'completed'"
                                class="mt-2 text-xs text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity"
                                @click.stop="deleteReconciliation(reconciliation)">
                                Delete
                            </button>
                        </div>

                        <!-- Connector -->
                        <div v-if="idx < visibleReconciliations.length - 1" class="flex-1"></div>
                    </template>

                    <!-- New period node -->
                    <div class="flex-1" v-if="!hasPendingReconciliation && reconciliations.length"></div>
                    <div class="flex flex-col items-center z-10 min-w-[140px]" v-if="!hasPendingReconciliation">
                        <div
                            class="rounded-full border-2 border-dashed border-gray-300 w-12 h-12 flex items-center justify-center mb-2 cursor-pointer hover:border-primary hover:bg-primary/5 transition-colors"
                            @click="startReconciliation">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-body-1/60">
                            {{ pluralize(unreconciledCount, 'new transaction', 'new transactions') }}
                        </span>
                        <button class="text-xs text-primary hover:underline mt-1" @click="startReconciliation">
                            Start reconciliation
                        </button>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="text-center py-8 text-body-1/60">
                    <p class="text-lg">No reconciliations yet</p>
                    <p class="text-sm mt-1">Start your first reconciliation to track your account balance against bank
                        statements.</p>
                    <LogerButton class="mt-4" @click="startReconciliation">
                        Start first reconciliation
                    </LogerButton>
                </div>

                <!-- Toggle / actions -->
                <div class="flex items-center space-x-4 mt-2" v-if="reconciliations.length > 4">
                    <button class="text-sm text-primary font-semibold hover:underline" @click="showAll = !showAll">
                        {{ showAll ? 'Show recent' : `Show all ${reconciliations.length} periods` }}
                    </button>
                </div>
            </section>

            <!-- Unreconciled Transactions Preview -->
            <section class="bg-base-lvl-3 rounded-lg shadow mt-4" v-if="previewTransactions.length">
                <header class="flex items-center justify-between px-6 py-4 border-b border-base-lvl-2">
                    <div>
                        <h3 class="font-bold text-body">Recent Unreconciled Transactions</h3>
                        <p class="text-xs text-body-1/60 mt-0.5">
                            {{ pluralize(unreconciledCount, 'transaction', 'transactions') }} since last reconciliation
                        </p>
                    </div>
                    <LogerButton variant="inverse" @click="startReconciliation" v-if="unreconciledCount > 0">
                        Reconcile now
                    </LogerButton>
                </header>

                <TransactionTable :cols="tableAccountCols(account.id)" :transactions="previewTransactions"
                    :is-loading="isLoading" @removed="removeTransaction($event, ['verified'])" />
            </section>

            <!-- Pending reconciliation banner -->
            <section
                class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mt-4 flex items-center justify-between"
                v-if="pendingReconciliation">
                <div>
                    <h4 class="font-bold text-yellow-800 dark:text-yellow-200">Pending Reconciliation</h4>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                        Reconciliation from {{ formatDate(pendingReconciliation.date) }} has a difference of
                        <span class="font-bold">{{ formatMoney(pendingReconciliation.difference, account.currency_code)
                            }}</span>
                    </p>
                </div>
                <LogerButton @click="navigateToReconciliation(pendingReconciliation)">
                    Review
                </LogerButton>
            </section>
        </FinanceTemplate>

        <!-- Reconciliation form modal -->
        <AccountReconciliationForm :is-visible="showReconcileForm" :account="account"
            @close="showReconcileForm = false" />
    </AppLayout>
</template>
